#!/bin/bash
# Run on the deploy host by ose_edocket.groovy:
#   ssh jenkins@<host> bash -s "<remote path>" "<image tag>" < deploy/remote/start.sh
# Replaces the running stack with the given image tag and waits for /up.
set -eu
REMOTE_PATH="$1"
IMAGE_TAG="$2"
cd "${REMOTE_PATH}"

compose() {
    IMAGE_TAG="${IMAGE_TAG}" docker compose \
        --env-file "${REMOTE_PATH}/.env_docker_compose" \
        --file "${REMOTE_PATH}/docker-compose.yaml" "$@"
}

echo "Stopping current stack"
compose down --remove-orphans

echo "Starting ${IMAGE_TAG}"
compose up -d

PORT=$(grep -E '^EDOCKET_APP_PORT=' "${REMOTE_PATH}/.env_docker_compose" | cut -d= -f2)
echo "Waiting for http://localhost:${PORT}/up"
for i in $(seq 1 45); do
    if curl -fsS -o /dev/null "http://localhost:${PORT}/up"; then
        echo "Healthy after ${i} checks"
        compose ps
        exit 0
    fi
    sleep 2
done

echo "Application did not become healthy; recent container logs:"
compose logs --tail=100
exit 1
