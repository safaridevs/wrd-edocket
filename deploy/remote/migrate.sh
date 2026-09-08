#!/bin/bash
# Run on the deploy host by ose_edocket.groovy:
#   ssh jenkins@<host> bash -s "<remote path>" "<image tag>" < deploy/remote/migrate.sh
# Migrates the target database with the NEW image before it starts serving.
#
# The secrets service is started first (with the new image) so the rendered
# .env exists in the shared tmpfs volume; `compose run app` then gets the app
# service's mounts, including that volume, without publishing its port.
set -eu
REMOTE_PATH="$1"
IMAGE_TAG="$2"
cd "${REMOTE_PATH}"

compose() {
    IMAGE_TAG="${IMAGE_TAG}" docker compose \
        --env-file "${REMOTE_PATH}/.env_docker_compose" \
        --file "${REMOTE_PATH}/docker-compose.yaml" "$@"
}

echo "Rendering secrets with ${IMAGE_TAG}"
if ! compose up -d --wait --wait-timeout 180 secrets; then
    echo "Secrets service did not become healthy; its logs:"
    compose logs --tail=50 secrets
    exit 1
fi

echo "Migrating with ${IMAGE_TAG}"
compose run --rm --no-deps --entrypoint "" app \
    php artisan migrate --force --no-interaction
