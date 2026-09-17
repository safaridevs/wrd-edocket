# Secrets: Jenkins-rendered `.env`

E-Docket secrets live in Jenkins credentials, never in git and never behind the
`coderepo:8088` key service. The committed `deploy/.env_<ENV>` file is both the
versioned config and the template: each secret value is a `'${VAR}'` placeholder
that the `Resolve .env` stage of `Jenkins_Pipelines/ose_edocket.groovy` renders
with `envsubst` at build time. The rendered `.env` is streamed to the deploy
host as a `0600` file and the container copies it into RAM at start.

```
deploy/.env_QAT  --(envsubst, Jenkins credentials)-->  workspace .env (0600)
                                    |
                                    | ssh, umask 077
                                    v
                  /opt/apps/edocket/qat/.env            (jenkins 0600, on disk)
                                    |
                                    | bind mount, read-only, read by the
                                    | container entrypoint as root
                                    v
                  /run/edocket/.env  (tmpfs, root:www-data 0640)
                  app container: /var/www/html/.env -> /run/edocket/.env
```

The reasons this replaced the `enc:` scheme are:

- the key was fetched over unauthenticated plain HTTP, so anything on the
  network could decrypt every committed value;
- it was a runtime dependency: an unreachable key service stopped the app
  from booting;
- encrypted values still lived in git, so one key exposure read the whole
  history.

`ApplicationUtils::getKey()/safeEncrypt()/safeDecrypt()/handleProperty()` and
the `ENC_PREFIX` variable are gone; `config/ldap.php` reads `LDAP_USERNAME`
and `LDAP_PASSWORD` directly.

Known limitation shared by every scheme: the value reaches the host in
plaintext because Laravel has to read it. The goal is managed, rotatable and
out of git, not "never plaintext anywhere". What the layout above does buy:
the host copy is `0600` and readable only by `jenkins` and `root`, and the copy
the application sees is in RAM, owned `root:www-data` `0640`, so nothing running
as `www-data` (including `php artisan key:generate` or a web shell) can rewrite
it, and it disappears when the container stops.

> **Why not Azure Key Vault.** A `secrets` sidecar that pulled these values from
> Key Vault at container start was built and verified in September 2026, and
> `kv-ose-shared-nonprod` already holds the `edocket-qat-*` values. Leadership
> has not chosen a cloud provider, so that path is parked until IT decides:
> see backlog `OPS-11`/`SEC-07`. The sidecar, `docker/fetch-secrets.py` and the
> Azure runbook are preserved in git at commit `12352db` and can be restored
> without touching the application — the container contract (`.env` arrives
> from outside, read-only, in RAM) is the same either way.

## Credentials

Each is a Jenkins **Secret text** credential, one set per environment, named
`edocket-<env>-<var-kebab-case>`. Create them in a folder scoped to the
E-Docket job so unrelated jobs cannot bind them.

| Variable | Credential id | Purpose |
|---|---|---|
| `APP_KEY` | `edocket-<env>-app-key` | Laravel encryption key. Mint a fresh one per environment: `base64:` + `openssl rand -base64 32` |
| `DB_PASSWORD` | `edocket-<env>-db-password` | SQL Server login named by `DB_USERNAME` in the template |
| `LDAP_USERNAME` | `edocket-<env>-ldap-username` | AD bind account. Was `enc:`-encrypted before, so it is treated as a secret |
| `LDAP_PASSWORD` | `edocket-<env>-ldap-password` | AD bind password |
| `BOUNCE_MAIL_PASSWORD` | `edocket-<env>-bounce-mail-password` | IMAP mailbox polled by `email:process-bounces` |

Five per environment, ten for QAT + UAT. `MAIL_PASSWORD` is `null` (the agency
relay on port 25 is unauthenticated) and is a literal in the template; the day
it gets a real value, add `edocket-<env>-mail-password` to the `withCredentials`
list, to `SECRET_VARS` in the render stage, and replace the literal with
`'${MAIL_PASSWORD}'`.

`APP_KEY` is unique per environment on purpose: a shared key lets a QAT key
holder forge UAT (and later PROD) session cookies. Rotating it only
invalidates active sessions; the app stores no encrypted-cast data.

## Render stage

- `withCredentials` binds the five ids for `deploy_environment` lowercased.
- Every value is checked before rendering: a credential that is empty, or that
  contains a single quote or a newline, fails the build by name. The template
  single-quotes each placeholder, which is how phpdotenv is told to take a value
  literally (`$`, `#`, spaces, backslashes all survive), and a single-quoted
  value has no escape for `'` itself. Pick passwords accordingly.
- `envsubst` substitutes **only** the listed variables. A bare `envsubst`
  would blank the literal `"${APP_NAME}"` references in `MAIL_FROM_NAME` and
  `VITE_APP_NAME`.
- Guard: a `${VAR}` in the template that is neither a bound credential nor
  `APP_NAME` fails the build. This catches a placeholder added to the template
  without a matching credential. It is checked on the template, not the
  rendered output, because a password may itself contain `${`.
- `set +x` and `umask 077` so rendered content reaches neither the build log nor
  a world-readable workspace file; Jenkins masking is exact-string only.
- The stage runs on every build, deploy or not, so the five credentials for the
  chosen environment must exist before the job runs at all. A missing
  credential fails loudly in `withCredentials` before anything is written.
- The workspace `.env` is deleted in the post block. The PHPUnit stage mounts
  only `reports/`, so the suite never sees it.

## On the host

`Ship config to server` streams the file over ssh (`umask 077 && cat > .env.new
&& mv -f .env.new .env`) rather than `scp`, because `scp` keeps the mode of an
existing file and would leave a world-readable `.env` in place. The compose file
bind-mounts it read-only at `/run/secrets/edocket.env`; `docker/entrypoint.sh`,
which runs as root, copies it to the `/run/edocket` tmpfs as `root:www-data`
`0640` and hands off to supervisord. `/var/www/html/.env` is a symlink to that
copy, baked into the image.

A missing or empty `/run/secrets/edocket.env` stops the container with a named
error rather than booting an app with no configuration.

## Rotation

1. Update the credential in Jenkins (and the password itself wherever it lives:
   SQL Server, AD, the mailbox).
2. Re-run the job with `Deploy` checked. Rendering happens at build time, so a
   rotation is a redeploy; there is no "restart and it picks it up".

For `APP_KEY`, expect every active session to be invalidated.

## Cutover

1. Recover the current plaintext values. `LDAP_USERNAME`/`LDAP_PASSWORD` are
   only held `enc:`-encrypted; from a machine on the OSE network run
   `php deploy/legacy-decrypt.php '<enc:value>'` for each. Do this once,
   before the key service is switched off. The values already loaded into
   `kv-ose-shared-nonprod` as `edocket-qat-*` are the same values and can be
   read back with `az keyvault secret show` instead.
2. Create the credentials (five for QAT, five for UAT). A Script Console seeder
   like wrats2's `deploy/secretseeder` works, but **do not commit it**: it holds
   every value in plaintext.
3. Confirm the SQL login for `DB_USERNAME` exists on each target database
   (the Windows-hosted app used integrated auth; the container cannot).
4. Run the job with `Deploy` unchecked first: the render, image build and
   test suite exercise every credential without touching a host.
5. Deploy QAT, then UAT.
6. **Rotate every secret afterwards.** The `enc:` values remain decryptable
   from git history for as long as the key service answers; moving to Jenkins
   only helps once the old values are dead. Rotation = update the credential,
   redeploy.
7. Delete `deploy/legacy-decrypt.php` once nothing depends on `coderepo:8088`.
   E-Docket itself no longer does; the unified-app branches still do (SEC-07).
8. Once the credentials are the source of truth, delete the `edocket-qat-*`
   secrets from `kv-ose-shared-nonprod` so there is one place to rotate. Keep
   the vault itself: it costs nothing idle and is the head start if Azure wins
   the cloud decision.

## Troubleshooting

```bash
docker logs edocket-qat                                  # entrypoint errors, apache, worker
docker exec edocket-qat ls -l /run/edocket/.env          # root:www-data 0640, non-empty
ls -l /opt/apps/edocket/qat/.env                         # jenkins 0600 on the host
docker exec edocket-qat runuser -u www-data -- php artisan about --only=environment
```

`/run/secrets/edocket.env is missing or empty` means the deploy directory has no
`.env`: the `Ship config to server` stage did not run (a `Deploy`-unchecked
build) or was run against a different path. A stale `.env` means the last
deploy rendered it — the host file is only written by the pipeline.
