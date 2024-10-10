#!/bin/bash

NETWORK=$(docker network ls --format="{{.Name}}" -f name=searchtweak-network | grep searchtweak-network$)

if [[ -z ${NETWORK} ]]
then
    docker network create searchtweak-network
fi

WINPTY=''
if [[ -n ${WINDIR} ]]
then
   echo "WINDIR is defined"
   WINPTY='winpty '
fi

NAME_PREFIX="$1"
ENV="${2:-local}"

docker compose -f docker-compose.yml -p $NAME_PREFIX stop
docker compose -f docker-compose.yml -p $NAME_PREFIX rm -f

docker compose -f docker-compose.yml -p $NAME_PREFIX build --pull
docker compose -f docker-compose.yml -p $NAME_PREFIX up -d --force-recreate
