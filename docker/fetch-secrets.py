#!/usr/bin/env python3
"""
edocket-secrets: render the application .env from a template plus Azure Key Vault.

Runs as the `secrets` service in deploy/<env>.docker-compose.yaml, from the same
image as the app, on the host network so it can reach the Azure Arc identity
endpoint that listens only on the host loopback. It

  1. reads the committed template (deploy/.env_<ENV>, shipped unrendered by
     Jenkins as .env.template) and collects every ${VAR} placeholder that is
     a secret (see placeholders());
  2. obtains a Key Vault access token from the host's Arc managed identity
     (AZURE_AUTH=arc, the default) or from a service-principal certificate
     (AZURE_AUTH=certificate);
  3. reads secret <SECRETS_PREFIX>-<var in kebab case> for each placeholder,
     e.g. APP_KEY -> edocket-qat-app-key;
  4. writes the rendered file atomically to SECRETS_OUTPUT, a tmpfs volume the
     app container mounts read-only, owned root:www-data mode 0640;
  5. stays alive so the file outlives `docker compose` restarts, re-rendering
     every SECRETS_REFRESH_SECONDS when that is set.

Usage:
  edocket-secrets            daemon mode (compose service)
  edocket-secrets --once     render and exit non-zero on failure
  edocket-secrets --list     print the secret names the template needs, no network

Only the Python standard library and the openssl binary are used, so nothing is
added to the image. Every setting is an environment variable; deploy/SECRETS.md
documents them and the Azure side.
"""

import base64
import grp
import hashlib
import json
import os
import re
import subprocess
import sys
import time
import urllib.error
import urllib.parse
import urllib.request
import uuid

PLACEHOLDER = re.compile(r"\$\{([A-Za-z_][A-Za-z0-9_]*)\}")
KEY_LINE = re.compile(r"^\s*(?:export\s+)?([A-Za-z_][A-Za-z0-9_]*)\s*=(.*)$")


def log(message):
    print(f"[edocket-secrets] {message}", file=sys.stderr, flush=True)


def setting(name, default=None, required=False):
    value = os.environ.get(name, "").strip()
    if value:
        return value
    if required:
        raise SystemExit(f"{name} is not set")
    return default


# ---------------------------------------------------------------------------
# HTTP
# ---------------------------------------------------------------------------

def http(method, url, headers=None, body=None, timeout=20):
    """Return (status, headers, text). HTTP error statuses are returned, not raised."""
    request = urllib.request.Request(url, data=body, method=method, headers=headers or {})
    try:
        with urllib.request.urlopen(request, timeout=timeout) as response:
            return response.status, response.headers, response.read().decode("utf-8", "replace")
    except urllib.error.HTTPError as error:
        return error.code, error.headers, error.read().decode("utf-8", "replace")


def b64url(data):
    return base64.urlsafe_b64encode(data).rstrip(b"=").decode("ascii")


# ---------------------------------------------------------------------------
# Tokens
# ---------------------------------------------------------------------------

def token_from_arc(resource):
    """Azure Arc managed identity: challenge/response against the local himds endpoint.

    The first call is answered 401 with a Www-Authenticate header naming a file
    under /var/opt/azcmagent/tokens that only root and the himds group can read.
    Sending its contents back as Basic auth proves the caller is on the machine.
    """
    endpoint = setting("AZURE_IMDS_ENDPOINT", "http://127.0.0.1:40342")
    url = (f"{endpoint}/metadata/identity/oauth2/token?api-version=2020-06-01"
           f"&resource={urllib.parse.quote(resource, safe='')}")

    status, headers, text = http("GET", url, {"Metadata": "true"})
    if status != 401:
        raise RuntimeError(f"Arc identity endpoint answered {status} to the challenge request: {text[:200]}")
    challenge = headers.get("Www-Authenticate", "")
    if "realm=" not in challenge:
        raise RuntimeError(f"Arc identity endpoint sent no challenge realm: {challenge!r}")
    key_path = challenge.split("realm=", 1)[1].strip().strip('"')
    try:
        with open(key_path, encoding="ascii") as handle:
            key = handle.read().strip()
    except OSError as error:
        raise RuntimeError(f"cannot read Arc challenge token {key_path}: {error} "
                           "(is /var/opt/azcmagent/tokens mounted into this container?)")

    status, _, text = http("GET", url, {"Metadata": "true", "Authorization": f"Basic {key}"})
    if status != 200:
        raise RuntimeError(f"Arc identity endpoint refused the token request: {status} {text[:200]}")
    return json.loads(text)["access_token"]


def token_from_certificate(resource):
    """Service principal with a certificate: RS256 client assertion, no client secret."""
    tenant = setting("AZURE_TENANT_ID", required=True)
    client = setting("AZURE_CLIENT_ID", required=True)
    cert_path = setting("AZURE_CLIENT_CERT", "/etc/edocket/identity/client.pem")
    authority = setting("AZURE_AUTHORITY_HOST", "https://login.microsoftonline.com").rstrip("/")
    token_url = f"{authority}/{tenant}/oauth2/v2.0/token"

    if not os.path.exists(cert_path):
        raise RuntimeError(f"certificate {cert_path} not found (PEM holding the private key and certificate)")

    der = subprocess.run(["openssl", "x509", "-in", cert_path, "-outform", "DER"],
                         capture_output=True, check=True).stdout
    now = int(time.time())
    header = {"alg": "RS256", "typ": "JWT", "x5t": b64url(hashlib.sha1(der).digest())}
    claims = {"aud": token_url, "iss": client, "sub": client, "jti": str(uuid.uuid4()),
              "nbf": now - 60, "exp": now + 600}
    signing_input = (b64url(json.dumps(header, separators=(",", ":")).encode()) + "." +
                     b64url(json.dumps(claims, separators=(",", ":")).encode()))
    signature = subprocess.run(["openssl", "dgst", "-sha256", "-sign", cert_path],
                               input=signing_input.encode(), capture_output=True, check=True).stdout
    assertion = f"{signing_input}.{b64url(signature)}"

    form = urllib.parse.urlencode({
        "client_id": client,
        "grant_type": "client_credentials",
        "scope": resource.rstrip("/") + "/.default",
        "client_assertion_type": "urn:ietf:params:oauth:client-assertion-type:jwt-bearer",
        "client_assertion": assertion,
    }).encode()
    status, _, text = http("POST", token_url, {"Content-Type": "application/x-www-form-urlencoded"}, form)
    if status != 200:
        raise RuntimeError(f"token endpoint refused the certificate assertion: {status} {text[:300]}")
    return json.loads(text)["access_token"]


def access_token(resource):
    mode = setting("AZURE_AUTH", "arc").lower()
    if mode == "arc":
        return token_from_arc(resource)
    if mode == "certificate":
        return token_from_certificate(resource)
    raise SystemExit(f"AZURE_AUTH must be 'arc' or 'certificate', not {mode!r}")


# ---------------------------------------------------------------------------
# Key Vault
# ---------------------------------------------------------------------------

def vault_url():
    explicit = setting("AZURE_KEYVAULT_URL")
    if explicit:
        return explicit.rstrip("/")
    return f"https://{setting('SECRETS_VAULT', required=True)}.vault.azure.net"


def read_secret(base, token, name):
    status, _, text = http("GET", f"{base}/secrets/{name}?api-version=7.4",
                           {"Authorization": f"Bearer {token}"})
    if status == 200:
        return json.loads(text)["value"]
    if status == 404:
        return None
    if status == 403:
        raise RuntimeError(f"forbidden reading {name}: the identity needs 'Key Vault Secrets User' on the vault")
    raise RuntimeError(f"reading {name} failed: {status} {text[:200]}")


def secret_name(prefix, variable):
    return f"{prefix}-{variable.lower().replace('_', '-')}"


# ---------------------------------------------------------------------------
# Template
# ---------------------------------------------------------------------------

def placeholders(template_text):
    """Variables in ${VAR} form that must come from the vault.

    A ${X} on the line that defines X itself (APP_KEY=${APP_KEY}) is a secret.
    A ${X} elsewhere, when X is defined by another line of the template
    (MAIL_FROM_NAME="${APP_NAME}"), is a Laravel Dotenv reference and is left
    for Dotenv to expand. Anything else is a secret embedded in a larger value.
    """
    defined = set()
    for line in template_text.splitlines():
        match = KEY_LINE.match(line)
        if match and not line.lstrip().startswith("#"):
            defined.add(match.group(1))

    wanted = []
    for line in template_text.splitlines():
        if line.lstrip().startswith("#"):
            continue
        match = KEY_LINE.match(line)
        key = match.group(1) if match else None
        for name in PLACEHOLDER.findall(line):
            if name != key and name in defined:
                continue
            if name not in wanted:
                wanted.append(name)
    return wanted


def dotenv_quote(value):
    """Double-quote a value so phpdotenv takes it literally.

    Single quotes have no escape at all in phpdotenv, so a value containing a
    quote can only be carried in double quotes, where backslash, double quote
    and dollar are escaped (dollar because "${X}" would otherwise be expanded).
    """
    return '"' + value.replace("\\", "\\\\").replace('"', '\\"').replace("$", "\\$") + '"'


def render(template_text, values):
    out = []
    for line in template_text.splitlines():
        if line.lstrip().startswith("#"):
            out.append(line)
            continue
        match = KEY_LINE.match(line)
        key = match.group(1) if match else None
        rhs = match.group(2).strip() if match else ""
        whole = PLACEHOLDER.fullmatch(rhs)
        if whole and whole.group(1) in values:
            out.append(f"{key}={dotenv_quote(values[whole.group(1)])}")
            continue
        out.append(PLACEHOLDER.sub(lambda m: values.get(m.group(1), m.group(0)), line))
    return "\n".join(out) + "\n"


def write_atomically(path, content):
    directory = os.path.dirname(path)
    os.makedirs(directory, exist_ok=True)
    try:
        gid = grp.getgrnam(setting("SECRETS_GROUP", "www-data")).gr_gid
    except KeyError:
        gid = 33
    os.chown(directory, 0, gid)
    os.chmod(directory, 0o750)
    tmp = f"{path}.tmp"
    fd = os.open(tmp, os.O_WRONLY | os.O_CREAT | os.O_TRUNC, 0o640)
    with os.fdopen(fd, "w", encoding="utf-8") as handle:
        handle.write(content)
    os.chown(tmp, 0, gid)
    os.chmod(tmp, 0o640)
    os.replace(tmp, path)


# ---------------------------------------------------------------------------
# Main
# ---------------------------------------------------------------------------

def render_once():
    template_path = setting("SECRETS_TEMPLATE", "/etc/edocket/env.template")
    output_path = setting("SECRETS_OUTPUT", "/run/edocket/.env")
    prefix = setting("SECRETS_PREFIX", required=True)
    resource = setting("AZURE_KEYVAULT_RESOURCE", "https://vault.azure.net")

    with open(template_path, encoding="utf-8") as handle:
        template_text = handle.read()
    wanted = placeholders(template_text)
    if not wanted:
        raise RuntimeError(f"{template_path} has no ${{VAR}} placeholders; nothing to fetch")

    base = vault_url()
    token = access_token(resource)
    values, missing = {}, []
    for variable in wanted:
        value = read_secret(base, token, secret_name(prefix, variable))
        if value is None:
            missing.append(secret_name(prefix, variable))
        else:
            values[variable] = value
    if missing:
        raise RuntimeError(f"secret(s) not found in {base}: {', '.join(missing)}")

    write_atomically(output_path, render(template_text, values))
    log(f"rendered {output_path} from {template_path}: {len(values)} secret(s) from {base}")


def with_retries(action, attempts, label):
    delay = 2
    for attempt in range(1, attempts + 1):
        try:
            return action()
        except Exception as error:  # noqa: BLE001 - every failure is reported and retried
            if attempt == attempts:
                raise
            log(f"{label} failed (attempt {attempt}/{attempts}): {error}; retrying in {delay}s")
            time.sleep(delay)
            delay = min(delay * 2, 30)


def main(argv):
    if "--list" in argv:
        prefix = setting("SECRETS_PREFIX", required=True)
        with open(setting("SECRETS_TEMPLATE", "/etc/edocket/env.template"), encoding="utf-8") as handle:
            for variable in placeholders(handle.read()):
                print(f"{variable:24} {secret_name(prefix, variable)}")
        return 0

    if "--once" in argv:
        with_retries(render_once, attempts=5, label="render")
        return 0

    # Daemon: never give up before the first successful render, since the app
    # container is waiting on this file; after that keep the last good render.
    refresh = int(setting("SECRETS_REFRESH_SECONDS", "0"))
    output_path = setting("SECRETS_OUTPUT", "/run/edocket/.env")
    delay = 2
    while True:
        try:
            render_once()
            delay = 2
        except Exception as error:  # noqa: BLE001
            if os.path.exists(output_path):
                log(f"refresh failed, keeping the previous render: {error}")
            else:
                log(f"render failed: {error}; retrying in {delay}s")
                time.sleep(delay)
                delay = min(delay * 2, 60)
                continue
        if refresh > 0:
            time.sleep(refresh)
        else:
            log("done; staying up so the rendered file survives compose restarts")
            while True:
                time.sleep(3600)


if __name__ == "__main__":
    try:
        sys.exit(main(sys.argv[1:]))
    except KeyboardInterrupt:
        sys.exit(130)
