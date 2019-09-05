#!/bin/bash

source ./.env

UID_VALUE=$(id -u)
GID_VALUE=$(id -g)

# output
echo UID_VAR=${UID_VALUE}
echo GID_VAR=${GID_VALUE}

# find and replace
sed -e "s/{{ UID_VALUE }}/${UID_VALUE}/g" \
    -e "s/{{ GID_VALUE }}/${GID_VALUE}/g" \
    -e "s/{{ APP_ENV }}/${APP_ENV}/g" \
    < template.docker-compose.yml \
    > docker-compose.yml

docker-compose up -d --build

echo "done"