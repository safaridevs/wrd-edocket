# Deploying E-Docket to QAT and UAT

E-Docket runs as one Docker container per environment on a Linux host, built
and shipped by the `ose_edocket` Jenkins job (`Jenkins_Pipelines/ose_edocket.groovy`).
The layout copies wrats2's (`ose_wrats2.groovy`): a dedicated Linux build
agent, an image tagged per build, and per-environment compose files in this
directory. It differs from wrats2 in one thing: Jenkins holds no secrets. The
config template is shipped unrendered and a `secrets` service on the host
fills it from Azure Key Vault at container start ([SECRETS.md](SECRETS.md)).

Production is not covered here. PROD still deploys through
`ose_unified_application.groovy` onto the Windows external host; moving it is
a separate decision (a `PROD` entry in the server map, a `deploy/.env_PROD`
template, five `edocket-prod-*` secrets in `kv-ose-shared-prod`, an identity for
the prod host, and a `--no-dev` image).

## What is where

| Path | Purpose |
|---|---|
| `Dockerfile` (repo root) | Apache + PHP 8.4 + sqlsrv/ldap/gd/intl, python3-pdfrw and poppler for the PDF tooling, supervisord running Apache, a queue worker and the scheduler |
| `docker/` | entrypoint, supervisord programs, Apache vhost, php.ini, and `fetch-secrets.py` (installed as `edocket-secrets`) |
| `deploy/.env_QAT`, `deploy/.env_UAT` | application config templates with `${VAR}` secret placeholders, shipped unrendered |
| `deploy/.env_docker_compose_<env>` | compose-level variables: host port, document and log directories, vault name and identity mode. Nothing secret |
| `deploy/<env>.docker-compose.yaml` | the stack: a `secrets` service that renders `.env` from Key Vault into a tmpfs volume, and the `app`, both from image `edocket-app:<tag>` |
| `deploy/remote/*.sh` | scripts the pipeline streams over ssh to the host (migrate, start, cleanup) |
| `deploy/legacy-decrypt.php` | one-time helper for the secrets cutover; delete afterwards |

On the host everything lives under `/opt/apps/edocket/<env>/`:

```
/opt/apps/edocket/qat/
  .env.template         application config with ${VAR} placeholders (from Jenkins)
  .env_docker_compose   port, paths, vault name, identity mode
  docker-compose.yaml
  documents/            bind-mounted as storage/app (private/ holds the filings)
  logs/                 bind-mounted as storage/logs
  identity/             only for AZURE_AUTH=certificate: client.pem, root 0600
```

The rendered `.env` is never on disk: it lives in the `edocket-<env>_secrets`
tmpfs volume, written by the `secrets` container and mounted read-only into the
app at `/run/edocket/.env`, which `/var/www/html/.env` links to.

## Host prerequisites

QAT and UAT both run on the shared non-prod host `10.64.85.45` (the wrats dev
server), alongside wrats2. Ports and naming follow
`~/ose-engineering/port-register.md`: E-Docket owns block 8010, QAT on 8011,
UAT on 8012. `DEPLOY_SERVER_MAP` at the top of the pipeline is the place to
change hosts. The host needs:

1. Docker Engine with the compose v2 plugin, and a `jenkins` user in the
   `docker` group whose `authorized_keys` holds the build agent's key. Same as
   wrats2, so on those hosts this already exists.
2. `/opt/apps/edocket` writable by `jenkins`. The per-environment directory is
   created by the pipeline; `documents/` and `logs/` are created by Docker on
   first start and chowned to `www-data` by the container entrypoint. Do not
   pre-create them as `jenkins`.
3. Inbound firewall rules for the compose ports (`8011` and `8012`).
4. Network paths from the host to SQL Server (`DB_HOST:1433`), AD
   (`ose.frose.local:389`) and the mail relay (`webmail.state.nm.us:25` and
   `:993`).
5. A SQL Server login for `DB_USERNAME` with rights on the environment's
   database. The old Windows deployment used integrated auth; a Linux
   container cannot.
6. An Azure identity for the host: the Azure Arc agent (preferred, nothing
   stored on the box) or a service-principal certificate under
   `/opt/apps/edocket/<env>/identity/`, plus outbound HTTPS to
   `kv-ose-shared-nonprod.vault.azure.net`. Both are walked through in
   [SECRETS.md](SECRETS.md).
7. The database itself. Either restore a copy of the dev database (`e_docket_dev`
   on `bpmstest`) and let the pipeline's migrate step bring it forward, or start
   from an empty database: as of 3 Sep 2026 the full migration history builds
   one from scratch on SQL Server (verified locally against the
   `mcr.microsoft.com/mssql/server:2022` image). That only works because the
   five migrations pruned in commit 8110b2b were restored under their original
   names — on an existing database they are already recorded and skipped.

The build agent (`app-healthcheck` label) needs Docker, `openssl`, `ssh` and
`scp`, which the wrats2 job already relies on. It needs no Azure tooling and
no credentials beyond the ssh key.

## What a build does

1. **Checkout** the branch (or `refs/tags/<tag>`) from GitHub, stamp
   `APP_VERSION=<branch>-<short sha>` and tag the image `edocket-app:<branch>-<sha>`.
2. **Prepare env template**: copy `deploy/.env_<ENV>` to `.env.template`,
   append `APP_VERSION`, and print the vault secret names it will need.
3. **Build** the image. QAT and UAT builds keep dev dependencies so PHPUnit
   is in the image.
4. **Test**: the full PHPUnit suite runs inside the image against in-memory
   SQLite (from `phpunit.xml`) with a throwaway `APP_KEY`, so tests never see
   the rendered `.env` or a real database. JUnit results are published.
5. With **Deploy** checked:
   - scp `.env.template`, the compose file and the compose env file to the host;
   - stream the image over ssh (`docker save | docker load`), no registry;
   - **migrate** the target database with the new image
     (`RUN_MIGRATIONS`, default on): the secrets service is started first
     so the rendered `.env` exists, then `compose run app php artisan migrate`;
   - `compose down` then `compose up -d --wait`, which waits for the secrets
     service to report the rendered file and for the app's healthcheck, then
     confirm `/up` from the host;
   - prune dangling layers. Tagged images stay for rollback.
6. Record the deploy in `\\unifiedappqat\deployments\deployments.json`, as
   every other job does.

A build with **Deploy** unchecked is a safe smoke test of the Dockerfile and
the test suite; it touches neither a host nor the vault.

## Rollback

Previous tags remain on the host (`docker images edocket-app`). To go back:

```bash
ssh jenkins@<host>
cd /opt/apps/edocket/<env>
IMAGE_TAG=<previous tag> docker compose --env-file .env_docker_compose --file docker-compose.yaml up -d
```

Migrations are not rolled back; if the newer build migrated, check whether
the older code tolerates the schema before switching.

## Operating

```bash
docker logs edocket-qat-secrets                  # vault fetch: token, names, errors
docker logs -f edocket-qat                       # apache, worker and scheduler output
tail -f /opt/apps/edocket/qat/logs/laravel.log        # application log
docker exec -it edocket-qat php artisan about    # config, version stamp, drivers
docker exec -it edocket-qat php artisan documents:index-text --limit=50
```

`config:cache` is deliberately not run. A few call sites still read `env()`
outside `config/` (`ApplicationUtils::getUsername()` reads `LDAP_ENGINE`,
`authenticate()` reads `JWT_SECRET`), and a cached config makes those return
null. Move them into `config/` before enabling it.

OCR (`documents:index-text --ocr`) needs OCRmyPDF, which is not in the image
by default. Build with `--build-arg WITH_OCR=true` to include it (about 400MB).
