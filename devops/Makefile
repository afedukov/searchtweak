.DEFAULT_GOAL := default

start:
	@bash scripts/start.sh searchtweak

stop:
	@bash scripts/stop.sh searchtweak

bootstrap:
	@bash scripts/bootstrap.sh

test:
	docker compose run --rm artisan test

jobs:
	docker compose run --rm artisan horizon

reverb:
	docker compose run --rm artisan reverb:start

vite:
	docker compose run --rm --service-ports npm run dev

seed:
	docker compose run --rm artisan migrate:fresh --seed

vite-prod:
	docker compose run --rm --service-ports npm run build

default:
	@$(MAKE) start
	@$(MAKE) bootstrap

%:
	@:
