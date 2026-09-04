#!/bin/bash
# Run on the deploy host after a successful start. Only dangling layers are
# pruned: previous tagged edocket-app images are kept so a rollback is
#   ssh jenkins@<host> bash -s /opt/apps/edocket/<env> <previous tag> < deploy/remote/start.sh
set +e
echo "Pruning dangling images"
docker image prune -f
echo "edocket-app images on this host:"
docker images edocket-app --format '{{.Tag}}\t{{.CreatedAt}}\t{{.Size}}'
