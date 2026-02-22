#!/usr/bin/env bash

set -euo pipefail

ACTION='\033[0;32m'
WARN='\033[1;33m'
RESET='\033[0m'

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
DEVOPS_DIR="$(cd "${SCRIPT_DIR}/.." && pwd)"
PROJECT_ROOT="$(cd "${DEVOPS_DIR}/.." && pwd)"

MODE="${1:-fresh}" # fresh | up

if [[ "${MODE}" != "fresh" && "${MODE}" != "up" ]]; then
    echo -e "${WARN}Unknown mode: ${MODE}${RESET}"
    echo "Usage: bash scripts/bootstrap.sh [fresh|up]"
    exit 1
fi

cd "${DEVOPS_DIR}"

ENV_FILE="${PROJECT_ROOT}/.env"
ENV_DIST_FILE="${PROJECT_ROOT}/.env.dist"

if [[ ! -f "${ENV_FILE}" ]]; then
    if [[ ! -f "${ENV_DIST_FILE}" ]]; then
        echo "Missing ${ENV_FILE} and ${ENV_DIST_FILE}"
        exit 1
    fi

    echo -e "${ACTION}.env file not found. Copying from .env.dist ...${RESET}"
    cp "${ENV_DIST_FILE}" "${ENV_FILE}"
else
    echo ".env file already exists. Skipping copy."
fi

APP_ENV="$(grep -E '^APP_ENV=' "${ENV_FILE}" | head -n1 | cut -d'=' -f2- | tr -d '"' || true)"
APP_ENV="${APP_ENV:-local}"
IS_PRODUCTION=false
if [[ "${APP_ENV}" == "production" ]]; then
    IS_PRODUCTION=true
fi

COMPOSER_FLAGS=(--optimize-autoloader)
if [[ "${IS_PRODUCTION}" == "true" ]]; then
    COMPOSER_FLAGS+=(--no-dev)
fi

echo -e "${ACTION}Installing composer dependencies ...${RESET}"
docker compose run --rm composer install "${COMPOSER_FLAGS[@]}"

if grep -q "^APP_KEY=base64" "${ENV_FILE}"; then
    echo "Application key already set. Skipping php artisan key:generate."
else
    echo -e "${ACTION}Generating application key...${RESET}"
    docker compose run --rm artisan key:generate
fi

if [[ "${MODE}" == "fresh" ]]; then
    echo -e "${WARN}Running destructive database reset (migrate:fresh --seed) ...${RESET}"
    docker compose run --rm artisan migrate:fresh --force --seed
else
    echo -e "${ACTION}Running safe migrations + seeding (migrate --seed) ...${RESET}"
    docker compose run --rm artisan migrate --force --seed
fi

echo -e "${ACTION}Clearing route/config/view caches ...${RESET}"
docker compose run --rm artisan route:clear
docker compose run --rm artisan config:clear
docker compose run --rm artisan view:clear

if [[ "${IS_PRODUCTION}" == "true" ]]; then
    echo -e "${ACTION}Caching routes/config/views for production ...${RESET}"
    docker compose run --rm artisan route:cache
    docker compose run --rm artisan config:cache
    docker compose run --rm artisan view:cache
else
    echo -e "${ACTION}Skipping cache warmup in ${APP_ENV} environment.${RESET}"
fi

echo -e "${ACTION}Publishing Livewire assets ...${RESET}"
docker compose run --rm artisan livewire:publish --assets

if [[ "${IS_PRODUCTION}" == "true" ]]; then
    echo -e "${ACTION}Terminating Horizon to reload workers ...${RESET}"
    docker compose run --rm artisan horizon:terminate
else
    echo -e "${ACTION}Skipping Horizon terminate in ${APP_ENV} environment.${RESET}"
fi

install_node_deps() {
    local lockfile="$1"
    shift

    if [[ -f "${lockfile}" ]]; then
        docker compose run --rm npm "$@" ci
    else
        docker compose run --rm npm "$@" install
    fi
}

echo -e "${ACTION}Installing node dependencies ...${RESET}"
install_node_deps "${PROJECT_ROOT}/package-lock.json"

echo -e "${ACTION}Building app resources ...${RESET}"
docker compose run --rm npm run build

echo -e "${ACTION}Installing docs dependencies ...${RESET}"
install_node_deps "${PROJECT_ROOT}/docs-site/package-lock.json" --prefix docs-site

echo -e "${ACTION}Publishing docs ...${RESET}"
docker compose run --rm npm --prefix docs-site run docs:publish

