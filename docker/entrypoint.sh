#!/bin/sh
# Container entrypoint. Runs as root so it can fix ownership on the bind
# mounts, then hands off to supervisord (Apache drops to www-data itself; the
# worker and scheduler are started as www-data by supervisord).
set -eu
cd /var/www/html

# The .env rendered by Jenkins is bind-mounted at /run/secrets/edocket.env. On
# the host it is jenkins-owned 0600, so www-data cannot read it through the
# mount; root can. Copy it into the /run/edocket tmpfs as root:www-data 0640,
# which /var/www/html/.env links to: readable by the app, not writable by it
# (key:generate included), and gone when the container stops.
SRC=/run/secrets/edocket.env
if [ ! -f "$SRC" ] || [ ! -s "$SRC" ]; then
    echo "edocket: $SRC is missing or empty -- the compose file bind-mounts the Jenkins-rendered .env from the deploy directory; see deploy/SECRETS.md" >&2
    exit 1
fi
install -d -o root -g www-data -m 0750 /run/edocket
install -o root -g www-data -m 0640 "$SRC" /run/edocket/.env

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
