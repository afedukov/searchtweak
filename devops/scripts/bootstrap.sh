#!/bin/bash

ACTION='\033[0;32m'

echo -e ${ACTION}Installing composer dependencies ...
docker compose run --rm composer install --optimize-autoloader --no-dev

# Check if .env file exists, if not, copy from .env.dist
if [ ! -f ../.env ]; then
    echo -e ${ACTION}.env file not found. Copying from .env.dist ...
    cp ../.env.dist ../.env
else
    echo ".env file already exists. Skipping copy."
fi

# Check if APP_KEY is already set in the .env file
if grep -q "^APP_KEY=base64" ../.env; then
    echo "Application key already set. Skipping php artisan key:generate."
else
    echo -e ${ACTION}Generating application key...
    docker compose run --rm artisan key:generate
fi

echo -e ${ACTION}Running migrations ...
docker compose run --rm artisan migrate --force

echo -e ${ACTION}Cleaning app cache ...
docker compose run --rm artisan cache:clear

echo -e ${ACTION}Cleaning route cache ...
docker compose run --rm artisan route:clear

echo -e ${ACTION}Cleaning views cache ...
docker compose run --rm artisan view:clear

echo -e ${ACTION}Caching routes ...
docker compose run --rm artisan route:cache

echo -e ${ACTION}Caching config ...
docker compose run --rm artisan config:cache

echo -e ${ACTION}Caching views ...
docker compose run --rm artisan view:cache

echo -e ${ACTION}Publishing livewire assets ...
docker compose run --rm artisan livewire:publish --assets

echo -e ${ACTION}Terminating horizon ...
docker compose run --rm artisan horizon:terminate

echo -e ${ACTION}Installing node dependencies ...
docker compose run --rm npm install

echo -e ${ACTION}Building app resources ...
docker compose run --rm npm run build

exit 0;
