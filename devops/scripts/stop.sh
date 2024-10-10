#!/bin/bash

NAME_PREFIX="$1"

docker compose -p $NAME_PREFIX stop
docker compose -p $NAME_PREFIX rm -f
