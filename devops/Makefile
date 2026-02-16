.DEFAULT_GOAL := default

start:
	@bash scripts/start.sh searchtweak

stop:
	@bash scripts/stop.sh searchtweak

bootstrap:
	@bash scripts/bootstrap.sh

test:
	docker compose run --rm artisan test

vite:
	docker compose run --rm --service-ports npm run dev

vite-prod:
	docker compose run --rm --service-ports npm run build

default:
	@$(MAKE) start
	@$(MAKE) bootstrap

%:
	@:
