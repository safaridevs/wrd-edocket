# OSE E-Docket (WRD)

Electronic docketing for the New Mexico Office of the State Engineer. Laravel 12
application covering the case lifecycle from ALU case creation through Hearing
Unit review, document acceptance and e-stamping, to closure and archival.

For how the application *behaves*, see [USER_GUIDE.md](USER_GUIDE.md),
[CASE_WORKFLOW_GUIDE.md](CASE_WORKFLOW_GUIDE.md) and
[QUICK_REFERENCE.md](QUICK_REFERENCE.md). This file covers getting it running
locally.

---

## Prerequisites

| Tool | Version | Notes |
|---|---|---|
| PHP | 8.2+ (8.4 recommended) | Extension list below |
| Composer | 2.x | |
| Node.js | 20+ | For Vite asset builds |
| Database | see [Databases](#databases) | SQL Server in production |
| Python | 3.x + `pdfrw` | Only for PDF stamping |

### Required PHP extensions

`ctype`, `curl`, `dom`, `fileinfo`, `filter`, `hash`, `iconv`, `json`,
`libxml`, `mbstring`, `openssl`, `pcre`, `phar`, `session`, `tokenizer`,
`xml`, `xmlwriter`, `zip` — plus a PDO driver for your database
(`pdo_sqlsrv`, `pdo_mysql`, `pdo_pgsql` or `pdo_sqlite`).

`ldap` is needed for Active Directory sign-in. Without it the application still
boots and authentication falls back to the database guard, which is enough for
local work.

---

## Getting started with Docker (recommended)

The bundled image carries all four PDO drivers plus `ldap`, `gd` and `intl`, so
you do not have to install anything into your host PHP. It also brings up
MySQL, PostgreSQL and SQL Server for testing against any supported driver.

```bash
cp .env.example .env
docker compose build app
docker compose up -d mysql postgres mssql
```

Now point `.env` at one of the containers. The app runs on the same Compose
network, so `DB_HOST` is the *service name*, not `localhost`, and the port is
the container's internal one rather than the mapped host port:

```dotenv
# MySQL
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=edocket_wrd
DB_USERNAME=edocket
DB_PASSWORD=secret
```

```dotenv
# PostgreSQL
DB_CONNECTION=pgsql
DB_HOST=postgres
DB_PORT=5432
DB_DATABASE=edocket_wrd
DB_USERNAME=edocket
DB_PASSWORD=secret
```

```dotenv
# SQL Server — matches production
DB_CONNECTION=sqlsrv
DB_HOST=mssql
DB_PORT=1433
DB_DATABASE=edocket_wrd
DB_USERNAME=sa
DB_PASSWORD=Str0ng!Passw0rd
DB_ENCRYPT=yes
DB_TRUST_SERVER_CERTIFICATE=true
```

Also set `APP_URL=http://localhost:8000`, and leave `LDAP_USERNAME` and
`LDAP_PASSWORD` empty unless you are on the OSE network (see
[Environment configuration](#environment-configuration)).

Then finish the setup and start the app:

```bash
docker compose run --rm app composer install
docker compose run --rm app php artisan key:generate
docker compose run --rm app npm install
docker compose run --rm app npm run build
docker compose run --rm app php artisan migrate
docker compose run --rm app php artisan storage:link
docker compose up -d app
```

The app is then on <http://localhost:8000>.

Ports are chosen to avoid colliding with other local services:

| Service | Host port | Container port |
|---|---|---|
| app | 8000 | 8000 |
| mysql | 33061 | 3306 |
| postgres | 54321 | 5432 |
| mssql | 14331 | 1433 |

SQL Server does not create a database from environment variables, so create it
once before migrating against `sqlsrv`:

```bash
docker compose exec mssql /opt/mssql-tools18/bin/sqlcmd \
  -S localhost -U sa -P "$MSSQL_SA_PASSWORD" -C \
  -Q "IF DB_ID('edocket_wrd') IS NULL CREATE DATABASE edocket_wrd;"
```

To point a command at a specific driver, override the connection on the fly:

```bash
docker compose run --rm \
  -e DB_CONNECTION=pgsql -e DB_HOST=postgres -e DB_PORT=5432 \
  -e DB_DATABASE=edocket_wrd -e DB_USERNAME=edocket -e DB_PASSWORD=secret \
  app php artisan migrate:fresh
```

Stop everything with `docker compose down`, or `docker compose down -v` to
discard the database volumes as well.

---

## Getting started on your own machine

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
npm install
npm run dev
```

Then in a second terminal:

```bash
php artisan serve
```

`composer dev` runs the server, queue worker, log tailer and Vite together in
one command if you prefer.

If your PHP lacks `ext-ldap` and you do not need Active Directory sign-in,
install dependencies with:

```bash
composer install --ignore-platform-req=ext-ldap
```

---

## Environment configuration

`.env.example` documents every variable the application reads. For a
development environment wired to the OSE network, ask the team for a working
`.env`. **Any copy you are given contains live credentials, so do not commit
your `.env` or share it onward.** Every `.env*` and `env.*` path is gitignored
except `.env.example`.

Secrets are plain values in `.env`. The earlier `enc:`-prefixed scheme, which
fetched a decryption key from `http://coderepo:8088` at config-load time, has
been removed: the QAT/UAT pipeline renders secrets from Jenkins credentials at
build time instead (see [deploy/SECRETS.md](deploy/SECRETS.md)). If you have an
old `.env` with `enc:` values, replace them with the plaintext.

### LDAP

For local work, the simplest approach is to leave `LDAP_USERNAME` and
`LDAP_PASSWORD` unset — sign-in then falls through to the database guard and
the seeded demo accounts work normally.

---

## Databases

`config/database.php` defaults to `sqlsrv`; production runs SQL Server. The
migration history also runs on MySQL, MariaDB, PostgreSQL and SQLite.

Set the driver with `DB_CONNECTION` and the usual `DB_HOST` / `DB_PORT` /
`DB_DATABASE` / `DB_USERNAME` / `DB_PASSWORD`. SQLite needs only:

```dotenv
DB_CONNECTION=sqlite
DB_DATABASE=/absolute/path/to/database/database.sqlite
```

Portability notes for anyone writing migrations:

- Reach for `App\Support\SchemaCompat` rather than querying `sys.*` tables
  directly. It expresses constraint, index and foreign-key operations for every
  supported driver.
- SQLite cannot add or drop a constraint without rebuilding the table, so
  `SchemaCompat` skips constraint work there. The columns those CHECK
  constraints guarded are validated in the application layer, and later
  migrations drop them anyway, so all drivers converge on the same schema.
- Keep raw SQL to ANSI syntax. `LOWER()`, `CASE WHEN` and `IN` are fine;
  `GETDATE()`, `TOP`, `ISNULL()` and `[bracket]` quoting are not.
- SQL Server and MySQL compare strings case-insensitively by default;
  PostgreSQL and SQLite do not. Do not rely on collation for
  case-insensitive matching — normalize the value instead.

---

## Demo data

```bash
php artisan demo:setup
```

This migrates, seeds demo cases and users, and prints the resulting accounts.
All demo passwords are `password123`. See [DEMO-SCENARIOS.md](DEMO-SCENARIOS.md)
for walkthroughs of each role.

---

## PDF stamping

Electronic stamping normalizes uploaded PDFs through a small Python helper
before applying the stamp:

```bash
pip install -r tools/pdf/requirements.txt
```

Configure with `PDF_CONVERSION_PYTHON` (interpreter to invoke),
`PDF_CONVERSION_SCRIPT`, `PDF_CONVERSION_VERSION` and
`PDF_CONVERSION_TIMEOUT`. Set `PDF_CONVERSION_ENABLED=false` to skip conversion
entirely if you are not exercising stamping locally.

---

## Day-to-day commands

```bash
php artisan test                     # test suite
./vendor/bin/pint                    # code style
php artisan migrate:fresh --seed     # rebuild the schema from scratch
php artisan queue:listen             # process queued jobs
php artisan pail                     # tail application logs
php artisan bounce:process           # poll the bounce mailbox
```

---

## Troubleshooting

**`Undefined constant "LDAP_OPT_REFERRALS"`** — `ext-ldap` is missing. Install
`php8.x-ldap`, or use the Docker image which already includes it.

**LDAP sign-in fails with an `enc:` value in `.env`** — the encrypted-secret
scheme is gone; put the plaintext value in `.env` (see
[Environment configuration](#environment-configuration)).

**`could not find driver`** — the PDO extension for your `DB_CONNECTION` is not
installed. Check `php -m` against the extension list above.

**Vite assets fail to load** — `vite.config.js` only allows origins on port
8000. Serve the app there, or add your origin to the `server.cors.origin` list.

**Files written by the container are owned by root** — the project is bind
mounted and the container runs as root, so `vendor/`, `node_modules/` and
`storage/` end up root-owned on the host. Either run commands as yourself:

```bash
docker compose run --rm --user "$(id -u):$(id -g)" app php artisan migrate
```

or fix ownership afterwards with
`sudo chown -R "$(id -u):$(id -g)" .`.

**`SQLSTATE[08001]` connecting to `mssql`** — SQL Server takes 20–30 seconds to
accept connections on first boot, and the `edocket_wrd` database has to be
created before the first migration. Check `docker compose ps` shows the service
as healthy, then run the `CREATE DATABASE` snippet above.

---

## Deploying to QAT and UAT

QAT and UAT run as a Docker container built from the root `Dockerfile` by the
`edocket` Jenkins job, from the `Jenkinsfile` in this repository. Per-
environment config lives in `deploy/`; secrets are Jenkins credentials
rendered into that config at build time, never committed. See
[deploy/README.md](deploy/README.md) for the host prerequisites and the deploy
flow, and [deploy/SECRETS.md](deploy/SECRETS.md) for the secret handling.

---

## Framework reference

Built on [Laravel](https://laravel.com); the framework
[documentation](https://laravel.com/docs) covers routing, Eloquent, queues and
the rest of the stack.
