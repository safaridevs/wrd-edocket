#!/bin/sh
# Container entrypoint. Runs as root so it can fix ownership on the bind
# mounts, then hands off to supervisord (Apache drops to www-data itself; the
# worker and scheduler are started as www-data by supervisord).
set -eu
cd /var/www/html

# /var/www/html/.env is a symlink to /run/edocket/.env, rendered from Azure Key
# Vault by the `secrets` service into the tmpfs volume both containers share.
# compose `depends_on: service_healthy` normally guarantees it exists before we
# start; after a host reboot Docker's restart policy brings both containers back
# without that ordering, so wait for it here as well.
WAIT="${SECRETS_WAIT_SECONDS:-120}"
i=0
while [ ! -s /run/edocket/.env ]; do
    if [ "$i" -ge "$WAIT" ]; then
        echo "edocket: /run/edocket/.env not rendered after ${WAIT}s -- check 'docker logs edocket-<env>-secrets'; see deploy/SECRETS.md" >&2
        exit 1
    fi
    [ "$i" -eq 0 ] && echo "edocket: waiting for the secrets service to render .env"
    i=$((i + 1))
    sleep 1
done

# storage/app and storage/logs are bind mounts. When the host directory did not
# exist, Docker created it root-owned, which Apache (www-data) cannot write to.
# The document store is chowned at the mount point only, never recursively: it
# can hold a lot of files, and everything inside was written by www-data anyway.
for d in storage/app storage/app/private storage/app/public; do
    mkdir -p "$d"
    chown www-data:www-data "$d"
done
# Logs are small, so repair them recursively: anything that ran artisan as root
# (an ad-hoc `docker run`) leaves a root-owned laravel.log that Apache cannot
# append to.
mkdir -p storage/logs
chown -R www-data:www-data storage/logs

# `edocket-entrypoint artisan <args>` runs an artisan command as www-data after
# the same setup, so files it creates (laravel.log, storage/app/*) have the
# right owner. deploy/remote/migrate.sh uses it for `migrate --force`.
if [ "${1:-}" = "artisan" ]; then
    shift
    echo "edocket: php artisan $*"
    exec runuser -u www-data -- php artisan "$@"
fi

# Compiled views are rebuilt per image; config is deliberately NOT cached
# because a few call sites still read env() at run time (see deploy/README.md).
runuser -u www-data -- sh -c 'php artisan view:clear -q && php artisan view:cache -q' || true

# Migrations are run explicitly by the pipeline (Run Migrations stage), not here,
# so a container restart never touches the schema.

echo "edocket: starting supervisord (apache, queue worker, scheduler)"
exec /usr/bin/supervisord -n -c /etc/supervisor/supervisord.conf
