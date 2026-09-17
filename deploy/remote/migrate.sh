#!/bin/bash
# Run on the deploy host by the Jenkinsfile:
#   ssh jenkins@<host> bash -s "<remote path>" "<image tag>" < deploy/remote/migrate.sh
# Migrates the target database with the NEW image before it starts serving.
#
# `compose run app` gets the app service's mounts, including the Jenkins-rendered
# .env, without publishing its port or touching the running container.
set -eu
REMOTE_PATH="$1"
IMAGE_TAG="$2"
cd "${REMOTE_PATH}"

compose() {
    IMAGE_TAG="${IMAGE_TAG}" docker compose \
        --env-file "${REMOTE_PATH}/.env_docker_compose" \
        --file "${REMOTE_PATH}/docker-compose.yaml" "$@"
}

[ -s "${REMOTE_PATH}/.env" ] || { echo "Missing ${REMOTE_PATH}/.env"; exit 1; }

echo "Migrating with ${IMAGE_TAG}"
# Through the entrypoint, not around it: it installs the rendered .env, fixes
# ownership on the bind mounts, and runs artisan as www-data so anything it
# creates (laravel.log) stays writable by Apache afterwards.
compose run --rm --no-deps app artisan migrate --force --no-interaction
