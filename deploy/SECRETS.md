# Secrets: Azure Key Vault, rendered at container start

E-Docket secrets live in Azure Key Vault. Nothing secret is in git, in Jenkins,
or on the deploy host's disk. The committed `deploy/.env_<ENV>` file is both the
versioned config and the template: each secret value is a `${VAR}` placeholder.
The pipeline ships that template unrendered, and a `secrets` service in the
compose stack fills it from the vault when the containers start.

```
deploy/.env_QAT  --(Jenkins scp, unrendered)-->  /opt/apps/edocket/qat/.env.template
                                                        |
   Azure Key Vault  <--(token from the host's identity)-- secrets service (edocket-secrets)
   kv-ose-shared-nonprod                                 |  renders into a tmpfs volume
   edocket-qat-*                                         v
                                              /run/edocket/.env  (RAM only, root:www-data 0640)
                                                        |
                                              app container: /var/www/html/.env -> /run/edocket/.env
```

Why this rather than Jenkins credentials (the wrats2 scheme):

- Jenkins is not a secret holder. A compromised agent, workspace or build log
  yields nothing.
- Every read is in the vault's audit log, and access is revoked per host
  identity, not by rotating what every job shares.
- Rotation is "change the vault, restart the secrets service". No git change,
  no Jenkins change, no redeploy.
- The rendered file exists only in memory, on a tmpfs volume, and is
  recreated after a reboot.

Why this rather than the old `enc:` scheme: the key was fetched over
unauthenticated HTTP, it was a runtime dependency on `coderepo:8088`, and the
encrypted values still lived in git history. `ApplicationUtils::getKey()`,
`safeEncrypt()`, `safeDecrypt()`, `handleProperty()` and `ENC_PREFIX` are gone.

What no scheme fixes: root on the deploy host can `docker exec` into the
running container and read the file. Key Vault buys audit, revocation and
rotation, not protection from the host's own administrators. That is why
prod and non-prod never share a vault or an identity.

## Secret names

One vault per tier, secrets named `<app>-<env>-<var in kebab case>`. The
secrets service derives the name from the placeholder, so adding a secret is:
add `${NEW_VAR}` to the template, create `edocket-<env>-new-var` in the vault,
redeploy. No pipeline change.

| Placeholder | Vault secret | Purpose |
|---|---|---|
| `${APP_KEY}` | `edocket-<env>-app-key` | Laravel encryption key. Mint one per environment: `base64:` + `openssl rand -base64 32` |
| `${DB_PASSWORD}` | `edocket-<env>-db-password` | SQL Server login named by `DB_USERNAME` in the template |
| `${LDAP_USERNAME}` | `edocket-<env>-ldap-username` | AD bind account. Treated as a secret because it was `enc:`-encrypted before |
| `${LDAP_PASSWORD}` | `edocket-<env>-ldap-password` | AD bind password |
| `${BOUNCE_MAIL_PASSWORD}` | `edocket-<env>-bounce-mail-password` | IMAP mailbox polled by `email:process-bounces` |

Vaults: `kv-ose-shared-nonprod` (QAT and UAT, shared host, shared identity) and
`kv-ose-shared-prod`. When E-Docket prod moves to the DMZ it gets `kv-ose-dmz-prod`
with only its own secrets and its own host identity.

`APP_KEY` is unique per environment on purpose: a shared key lets a QAT key
holder forge UAT session cookies. `MAIL_PASSWORD` is `null` (the agency relay
on port 25 is unauthenticated) and stays a literal in the template.

To see exactly which secrets a template needs, from any machine with the image:

```bash
docker run --rm --entrypoint "" -v "$PWD/deploy/.env_QAT:/etc/edocket/env.template:ro" \
    -e SECRETS_PREFIX=edocket-qat edocket-app:<tag> edocket-secrets --list
```

## Azure setup

Everything below needs Owner (or Contributor plus User Access Administrator)
on the `OSE_WebApplications` subscription. Portal equivalents exist for each
step; the CLI form is given because it is repeatable. Substitute the region
the subscription already uses for `<region>`.

### 1. Resource group and providers

```bash
az group create --name rg-ose-secrets --location <region>   # vaults and their audit workspace
az group create --name rg-ose-servers --location <region>   # Arc machine registrations
# Only needed for the Arc identity option:
for p in Microsoft.HybridCompute Microsoft.HybridConnectivity Microsoft.GuestConfiguration; do
    az provider register --namespace $p
done
```

### 2. Vaults

Key Vault names are globally unique DNS labels. Check first, then create with
the RBAC permission model (not access policies) and purge protection on:

```bash
az keyvault check-name --name kv-ose-shared-nonprod
az keyvault create --name kv-ose-shared-nonprod --resource-group rg-ose-secrets --location <region> \
    --enable-rbac-authorization true --enable-purge-protection true --retention-days 90
# Later, when a production app moves to a container (not before):
az keyvault create --name kv-ose-shared-prod    --resource-group rg-ose-secrets --location <region> \
    --enable-rbac-authorization true --enable-purge-protection true --retention-days 90
```

Region: West US 3 (Phoenix), the closest region to New Mexico and where the
subscription's existing resources sit; use it for everything here.

Turn on audit logging, which is half the point of the move. A Log Analytics
workspace costs cents at this volume:

```bash
az monitor log-analytics workspace create --resource-group rg-ose-secrets --workspace-name log-ose-secrets
WS=$(az monitor log-analytics workspace show -g rg-ose-secrets -n log-ose-secrets --query id -o tsv)
for v in kv-ose-shared-nonprod kv-ose-shared-prod; do
    az monitor diagnostic-settings create --name audit --workspace "$WS" \
        --resource "$(az keyvault show -n $v --query id -o tsv)" \
        --logs '[{"category":"AuditEvent","enabled":true}]'
done
```

Vault cost is per operation (about three cents per ten thousand reads). Each
container start reads five secrets. The bill rounds to zero.

### 3. Who may write

Grant yourself, and whoever else loads values, `Key Vault Secrets Officer` on
the vault. Keep it scoped to the vault, not the resource group or subscription.

```bash
ME=$(az ad signed-in-user show --query id -o tsv)
az role assignment create --assignee-object-id "$ME" --assignee-principal-type User \
    --role "Key Vault Secrets Officer" --scope "$(az keyvault show -n kv-ose-shared-nonprod --query id -o tsv)"
```

Do not give any host identity Officer. Hosts read; people write.

### 4. Load the secrets

```bash
az keyvault secret set --vault-name kv-ose-shared-nonprod --name edocket-qat-app-key \
    --value "base64:$(openssl rand -base64 32)"
# Passwords: read from a prompt so they stay out of shell history.
read -rs -p "edocket-qat-db-password: " V; echo
az keyvault secret set --vault-name kv-ose-shared-nonprod --name edocket-qat-db-password --value "$V"; unset V
```

Repeat for the five `edocket-qat-*` and five `edocket-uat-*` names in the
table above. The existing `LDAP_USERNAME` and `LDAP_PASSWORD` are only held
`enc:`-encrypted; from a machine on the OSE network run
`php deploy/legacy-decrypt.php '<enc:value>'` once to recover them, while
`coderepo:8088` still answers.

### 5. Who may read: the host identity

The container proves who it is with the host's identity. Two options; the
compose files support both through `.env_docker_compose_<env>`.

**Option A, Azure Arc managed identity (preferred).** No credential of any
kind is stored on the host.

1. Portal: Azure Arc, Machines, Add a single server, Linux. Generate the
   onboarding script into `rg-ose-servers`, run it as root on the host.
2. On the host, `azcmagent check` confirms the outbound path to Azure. This
   is the only step that can fail for reasons outside your control (network
   egress rules); test it before anything else.
3. Grant the machine's identity read on the non-prod vault:

   ```bash
   PID=$(az connectedmachine show -g rg-ose-servers -n <hostname> --query identity.principalId -o tsv)
   az role assignment create --assignee-object-id "$PID" --assignee-principal-type ServicePrincipal \
       --role "Key Vault Secrets User" --scope "$(az keyvault show -n kv-ose-shared-nonprod --query id -o tsv)"
   ```

4. Nothing to configure on the host or in the repo: `AZURE_AUTH=arc` is the
   default. The secrets service runs on the host network, calls the Arc
   endpoint on `127.0.0.1:40342`, and answers its challenge with the token
   file Docker mounts from `/var/opt/azcmagent/tokens`.

Arc-enabled servers and their identity are free. Charges only start if you
attach Defender for Servers, Update Manager or similar.

**Option B, service principal with a certificate.** Works without Arc. One
credential file lives on the host, root-owned; if it leaks, revoke that one
identity in Entra ID.

```bash
APP=$(az ad app create --display-name ose-edocket-nonprod --query appId -o tsv)
az ad sp create --id "$APP" >/dev/null
openssl req -x509 -newkey rsa:2048 -nodes -days 365 -subj "/CN=ose-edocket-nonprod" \
    -keyout key.pem -out cert.pem
az ad app credential reset --id "$APP" --cert "@cert.pem" --append
SP=$(az ad sp show --id "$APP" --query id -o tsv)
az role assignment create --assignee-object-id "$SP" --assignee-principal-type ServicePrincipal \
    --role "Key Vault Secrets User" --scope "$(az keyvault show -n kv-ose-shared-nonprod --query id -o tsv)"
cat key.pem cert.pem > client.pem && shred -u key.pem
```

Install `client.pem` on the host as `/opt/apps/edocket/<env>/identity/client.pem`,
owner root, mode 0600 (the secrets container runs as root, so it can read it;
the `jenkins` user cannot). Then in `deploy/.env_docker_compose_<env>` set
`AZURE_AUTH=certificate`, `AZURE_TENANT_ID` and `AZURE_CLIENT_ID` (the
`appId`). The certificate expires; put its renewal in the calendar.

Either way, `Key Vault Secrets User` can be assigned at the scope of a single
secret instead of the vault if a host should ever read only some of them.

### 6. Network

The host needs outbound HTTPS to `<vault>.vault.azure.net` and, for the
certificate option, `login.microsoftonline.com`. The vault firewall can be
narrowed to the agency's egress address once that is known; leave it open to
"all networks" for the first deploy so a firewall rule is not mistaken for an
identity problem.

For Azure Government or another sovereign cloud, set `AZURE_KEYVAULT_URL`,
`AZURE_KEYVAULT_RESOURCE` and `AZURE_AUTHORITY_HOST` in
`.env_docker_compose_<env>`; the defaults are the public cloud.

## How the container side works

`docker/fetch-secrets.py` is installed in the image as `edocket-secrets` and
runs as the `secrets` service in `deploy/<env>.docker-compose.yaml`:

- same image as the app, `network_mode: host` (the Arc endpoint listens on the
  host loopback only; the service publishes nothing), runs as root to read the
  Arc challenge token;
- reads `.env.template`, collects the `${VAR}` placeholders (a `${X}` on a
  line other than the one defining `X`, such as `"${APP_NAME}"`, is a Dotenv
  reference and is left alone), fetches `<prefix>-<var>` for each, and writes
  the result atomically to the `secrets` tmpfs volume, root:www-data 0640.
  Values are double-quoted with phpdotenv's escaping, so any character is safe;
- reports healthy once the file exists; the app has
  `depends_on: condition: service_healthy`, and its entrypoint also waits for
  the file so a host reboot (where Docker restarts both without ordering)
  works;
- stays up so the file survives `compose` restarts. With
  `SECRETS_REFRESH_SECONDS>0` it re-reads the vault on that interval and keeps
  the last good render on failure.

The app never talks to Azure. Azure being unreachable stops a container from
*starting*, never a running one from serving.

Migrations (`deploy/remote/migrate.sh`) start the secrets service with the
new image first, then run `compose run --no-deps app php artisan migrate`, which
gets the same volume.

### Rotation

1. Set the new value in the vault. For the database or AD password, change it
   on that system at the same time.
2. On the host: `cd /opt/apps/edocket/<env> && docker compose restart secrets`
   (or wait for the refresh interval, if set). Laravel reads `.env` per
   request, so the app picks the new value up without a restart. The queue
   worker holds its connection until its `--max-time` (an hour) or
   `docker compose restart app`.

### Troubleshooting on the host

```bash
docker logs edocket-qat-secrets                     # token and vault errors, by name
cd /opt/apps/edocket/qat
IMAGE_TAG=<tag> docker compose --env-file .env_docker_compose -f docker-compose.yaml \
    run --rm --no-deps secrets --list               # which names it will ask for
IMAGE_TAG=<tag> docker compose --env-file .env_docker_compose -f docker-compose.yaml \
    run --rm --no-deps secrets --once               # one render, exit non-zero on failure
```

`403 forbidden` means the identity lacks `Key Vault Secrets User` on that
vault. `not found` names the missing secret. A challenge error mentioning
`/var/opt/azcmagent/tokens` means Arc is not installed on the host, so either
onboard it or switch to the certificate option.

## Cutover

1. Recover the current `enc:` LDAP values (step 4 above) before the key
   service is switched off.
2. Create the vaults, load the ten secrets, and set up the host identity.
3. Confirm the SQL login for `DB_USERNAME` exists on each target database
   (the Windows-hosted app used integrated auth; the container cannot).
4. Run the job with `Deploy` unchecked first: it builds and tests the image
   and lists the secret names the template needs, without touching a host.
5. Deploy QAT. If the secrets service is not healthy within three minutes the
   start script prints its log and fails; nothing was changed on the host
   except the shipped files.
6. Deploy UAT.
7. **Rotate every secret afterwards.** The `enc:` values remain decryptable
   from git history for as long as the key service answers; the move only
   helps once the old values are dead.
8. Delete `deploy/legacy-decrypt.php` once nothing depends on `coderepo:8088`.
   E-Docket itself no longer does; the unified-app branches still do (SEC-07).
