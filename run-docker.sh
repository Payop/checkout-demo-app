#!/usr/bin/env bash
set -euo pipefail

source ./.env

UID_VALUE=$(id -u)
GID_VALUE=$(id -g)


if command -v docker-compose >/dev/null 2>&1; then
    compose=(docker-compose)
elif docker compose version >/dev/null 2>&1; then
    compose=(docker compose)
elif command -v docker >/dev/null 2>&1; then
    echo "Error: Docker installed, but module Docker Compose does not exist ." >&2
    exit 1
else
    echo "Error: Docker does not installed." >&2
    exit 1
fi

echo "Used: ${compose[*]}"

# output
echo UID_VAR=${UID_VALUE}
echo GID_VAR=${GID_VALUE}

# find and replace
sed -e "s/{{ UID_VALUE }}/${UID_VALUE}/g" \
    -e "s/{{ GID_VALUE }}/${GID_VALUE}/g" \
    -e "s/{{ APP_ENV }}/${APP_ENV}/g" \
    < template.docker-compose.yml \
    > docker-compose.yml

"${compose[@]}" up -d --build
  
echo "done"