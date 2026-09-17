# Deploying E-Docket to QAT and UAT

E-Docket runs as one Docker container per environment on a Linux host, built
and shipped by the `ose_edocket` Jenkins job (`Jenkins_Pipelines/ose_edocket.groovy`).
The layout copies wrats2's (`ose_wrats2.groovy`): a dedicated Linux build
agent, an image tagged per build, and per-environment compose files in this
directory, and secrets held as Jenkins credentials and rendered into the
committed config template at build time ([SECRETS.md](SECRETS.md)).

Production is not covered here. PROD still deploys through
`ose_unified_application.groovy` onto the Windows external host; moving it is
a separate decision (a `PROD` entry in the server map, a `deploy/.env_PROD`
template, five `edocket-prod-*` credentials, and a `--no-dev` image).

## What is where

| Path | Purpose |
|---|---|
| `Dockerfile` (repo root) | Apache + PHP 8.4 + sqlsrv/ldap/gd/intl, python3-pdfrw and poppler for the PDF tooling, supervisord running Apache, a queue worker and the scheduler |
| `docker/` | entrypoint, supervisord programs, Apache vhost, php.ini |
| `deploy/.env_QAT`, `deploy/.env_UAT` | application config templates; `'${VAR}'` placeholders are rendered from Jenkins credentials at build time |
| `deploy/.env_docker_compose_<env>` | compose-level variables: host port, document and log directories. Nothing secret |
| `deploy/<env>.docker-compose.yaml` | the stack: the `app` service from image `edocket-app:<tag>`, with the rendered `.env` bind-mounted |
| `deploy/remote/*.sh` | scripts the pipeline streams over ssh to the host (migrate, start, cleanup) |
| `deploy/legacy-decrypt.php` | one-time helper for the secrets cutover; delete afterwards |

On the host everything lives under `/opt/apps/edocket/<env>/`:

```
/opt/apps/edocket/qat/
  .env                  rendered application config, jenkins-owned 0600
  .env_docker_compose   port and paths
  docker-compose.yaml
  documents/            bind-mounted as storage/app (private/ holds the filings)
  logs/                 bind-mounted as storage/logs
```

`.env` is bind-mounted read-only at `/run/secrets/edocket.env`; the entrypoint
copies it into a tmpfs at `/run/edocket/.env` as `root:www-data` `0640`, which
`/var/www/html/.env` links to. The application can read its configuration and
nothing running as `www-data` can rewrite it.

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
6. The database itself. Either restore a copy of the dev database (`e_docket_dev`
   on `bpmstest`) and let the pipeline's migrate step bring it forward, or start
   from an empty database: as of 3 Sep 2026 the full migration history builds
   one from scratch on SQL Server (verified locally against the
   `mcr.microsoft.com/mssql/server:2022` image). That only works because the
   five migrations pruned in commit 8110b2b were restored under their original
   names — on an existing database they are already recorded and skipped.

The build agent (`app-healthcheck` label) needs Docker, `openssl`, `ssh` and
`scp` and `envsubst` (`gettext-base`), all of which the wrats2 job already uses.

## What a build does

1. **Checkout** the branch (or `refs/tags/<tag>`) from GitHub, stamp
   `APP_VERSION=<branch>-<short sha>` and tag the image `edocket-app:<branch>-<sha>`.
2. **Resolve .env**: render `deploy/.env_<ENV>` with the five
   `edocket-<env>-*` Jenkins credentials and append `APP_VERSION`. Runs on
   every build, so a build-only run proves the credentials exist.
3. **Build** the image. QAT and UAT builds keep dev dependencies so PHPUnit
   is in the image.
4. **Test**: the full PHPUnit suite runs inside the image against in-memory
   SQLite (from `phpunit.xml`) with a throwaway `APP_KEY`, so tests never see
   the rendered `.env` or a real database. JUnit results are published.
5. With **Deploy** checked:
   - ship the rendered `.env` (streamed over ssh, `0600`), the compose file
     and the compose env file to the host;
   - stream the image over ssh (`docker save | docker load`), no registry;
   - **migrate** the target database with the new image
     (`RUN_MIGRATIONS`, default on) with `compose run app artisan migrate`,
     through the entrypoint so it runs as `www-data`;
   - `compose down` then `compose up -d --wait`, which waits for the app's
     healthcheck, then confirm `/up` from the host;
   - prune dangling layers. Tagged images stay for rollback.
6. Record the deploy in `\\unifiedappqat\deployments\deployments.json`, as
   every other job does.

A build with **Deploy** unchecked is a safe smoke test of the Dockerfile and
the test suite; it binds the credentials but touches no host.

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
docker logs -f edocket-qat                       # apache, worker and scheduler output
tail -f /opt/apps/edocket/qat/logs/laravel.log        # application log
docker exec -it edocket-qat runuser -u www-data -- php artisan about   # run artisan as www-data, never as root
docker exec -it edocket-qat runuser -u www-data -- php artisan documents:index-text --limit=50
```

`config:cache` is deliberately not run. A few call sites still read `env()`
outside `config/` (`ApplicationUtils::getUsername()` reads `LDAP_ENGINE`,
`authenticate()` reads `JWT_SECRET`), and a cached config makes those return
null. Move them into `config/` before enabling it.

Always run artisan inside the container as `www-data` (as above). Run as root,
it creates root-owned files under `storage/` that Apache can no longer write,
which shows up as "laravel.log could not be opened in append mode". The
entrypoint repairs `storage/logs` on the next start, but not `storage/app`.

OCR (`documents:index-text --ocr`) needs OCRmyPDF, which is not in the image
by default. Build with `--build-arg WITH_OCR=true` to include it (about 400MB).
