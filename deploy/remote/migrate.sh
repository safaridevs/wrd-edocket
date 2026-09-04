#!/bin/bash
# Run on the deploy host by ose_edocket.groovy:
#   ssh jenkins@<host> bash -s "<remote path>" "<image:tag>" < deploy/remote/migrate.sh
# Migrates the target database with the NEW image before it starts serving.
# The rendered .env is bind-mounted (not --env-file) so Laravel parses quoting
# and ${APP_NAME} references itself.
set -eu
REMOTE_PATH="$1"
IMAGE="$2"

echo "Migrating with ${IMAGE} using ${REMOTE_PATH}/.env"
docker run --rm --entrypoint "" \
    -v "${REMOTE_PATH}/.env:/var/www/html/.env:ro" \
    "${IMAGE}" \
    sh -c "php artisan migrate --force --no-interaction"
