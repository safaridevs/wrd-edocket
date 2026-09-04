#!/bin/sh
# Container entrypoint. Runs as root so it can fix ownership on the bind
# mounts, then hands off to supervisord (Apache drops to www-data itself; the
# worker and scheduler are started as www-data by supervisord).
set -eu
cd /var/www/html

if [ ! -f .env ]; then
    echo "edocket: /var/www/html/.env is missing -- the compose file bind-mounts the rendered .env; see deploy/README.md" >&2
    exit 1
fi

# storage/app and storage/logs are bind mounts. When the host directory did not
# exist, Docker created it root-owned, which Apache (www-data) cannot write to.
# Only the mount points are chowned, never recursively: the document store can
# hold a lot of files, and everything inside was written by www-data anyway.
for d in storage/app storage/app/private storage/app/public storage/logs; do
    mkdir -p "$d"
    chown www-data:www-data "$d"
done

# Compiled views are rebuilt per image; config is deliberately NOT cached
# because a few call sites still read env() at run time (see deploy/README.md).
su -s /bin/sh www-data -c 'php artisan view:clear -q && php artisan view:cache -q' || true

# Migrations are run explicitly by the pipeline (Run Migrations stage), not here,
# so a container restart never touches the schema.

echo "edocket: starting supervisord (apache, queue worker, scheduler)"
exec /usr/bin/supervisord -n -c /etc/supervisor/supervisord.conf
