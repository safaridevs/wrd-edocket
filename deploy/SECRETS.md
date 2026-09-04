# Secrets: Jenkins-rendered .env

E-Docket secrets live in Jenkins credentials, never in git and never behind
the `coderepo:8088` key service. The committed `deploy/.env_<ENV>` file is
both the versioned config and the template: each secret value is a `${VAR}`
placeholder that the `Resolve .env` stage of `Jenkins_Pipelines/ose_edocket.groovy`
renders with `envsubst` at build time. The rendered `.env` is scp'd to the
deploy host and bind-mounted read-only into the container.

The reasons it replaced the `enc:` scheme are:

- the key was fetched over unauthenticated plain HTTP, so anything on the
  network could decrypt every committed value;
- it was a runtime dependency: an unreachable key service stopped the app
  from booting;
- encrypted values still lived in git, so one key exposure read the whole
  history.

`ApplicationUtils::getKey()/safeEncrypt()/safeDecrypt()/handleProperty()` and
the `ENC_PREFIX` variable are gone; `config/ldap.php` reads `LDAP_USERNAME`
and `LDAP_PASSWORD` directly.

Known limitation shared by every scheme: the deploy host holds the rendered
`.env` in plaintext because Laravel has to read it. The goal is managed,
rotatable and out of git, not "never plaintext anywhere".

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
list and the `envsubst` variable list in the pipeline, and replace the literal
with `${MAIL_PASSWORD}`.

`APP_KEY` is unique per environment on purpose: a shared key lets a QAT key
holder forge UAT (and later PROD) session cookies. Rotating it only
invalidates active sessions; the app stores no encrypted-cast data.

## Render stage

- `withCredentials` binds the five ids for `deploy_environment` lowercased.
- `envsubst` substitutes **only** the listed variables. A bare `envsubst`
  would blank the literal `"${APP_NAME}"` references in `MAIL_FROM_NAME` and
  `VITE_APP_NAME`.
- Guard: any `${` left in a non-comment line other than an `APP_NAME`
  reference fails the build. This catches a placeholder added to the template
  without a matching variable in the substitution list.
- `set +x` so rendered content never reaches the build log; Jenkins masking
  is exact-string only.
- The stage runs on every build, deploy or not, so all ten credentials must
  exist before the job runs at all. A missing credential fails loudly in
  `withCredentials` before anything is written.
- The workspace `.env` is deleted in the post block.

## Cutover

1. Recover the current plaintext values. `LDAP_USERNAME`/`LDAP_PASSWORD` are
   only held `enc:`-encrypted; from a machine on the OSE network run
   `php deploy/legacy-decrypt.php '<enc:value>'` for each. Do this once,
   before the key service is switched off.
2. Create the ten credentials. A Script Console seeder like wrats2's
   `deploy/secretseeder` works, but **do not commit it**: it holds every
   value in plaintext.
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
