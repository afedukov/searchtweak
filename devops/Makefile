.DEFAULT_GOAL := default

start:
	@bash scripts/start.sh searchtweak

stop:
	@bash scripts/stop.sh searchtweak

bootstrap:
	@bash scripts/bootstrap.sh

bootstrap-up:
	@bash scripts/bootstrap.sh up

bootstrap-fresh:
	@bash scripts/bootstrap.sh fresh

test:
	docker compose run --rm artisan config:clear
	docker compose run --rm artisan test

jobs:
	docker compose run --rm artisan horizon

queue-reload:
	docker exec searchtweak-queue php artisan horizon:terminate

reverb:
	docker compose run --rm artisan reverb:start

vite:
	docker compose run --rm --service-ports npm run dev

vite-prod:
	docker compose run --rm --service-ports npm run build

seed:
	docker compose run --rm artisan migrate:fresh --seed

docs:
	docker compose run --rm -p 3001:3001 npm --prefix docs-site run docs:dev

docs-install:
	docker compose run --rm npm --prefix docs-site install

docs-build:
	docker compose run --rm npm --prefix docs-site run docs:build

docs-preview:
	docker compose run --rm -p 3001:3001 npm --prefix docs-site run docs:preview

docs-publish:
	docker compose run --rm npm --prefix docs-site run docs:publish

default:
	@$(MAKE) start
	@$(MAKE) bootstrap

%:
	@:
