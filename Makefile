.PHONY: build up down restart logs shell migrate seed fresh test vendor lint

COMPOSE      = docker compose
COMPOSE_TEST = docker compose -f docker-compose.test.yml -p asset-register-test

# First-time setup: build images, start containers, migrate, and seed.
build: .env
	$(COMPOSE) build
	$(COMPOSE) up -d
	$(COMPOSE) exec app php artisan migrate --force
	$(COMPOSE) exec app php artisan db:seed --force

# Create .env from .env.example and generate APP_KEY if missing.
.env:
	cp .env.example .env
	@grep -q '^APP_KEY=.' .env || \
		( KEY=$$(openssl rand -base64 32) && \
		  sed -i.bak "s|^APP_KEY=.*|APP_KEY=base64:$$KEY|" .env && \
		  rm -f .env.bak )
	@echo ".env ready"

# Rebuild images and start local dev containers; run any pending migrations.
up: .env
	$(COMPOSE) up -d --build
	$(COMPOSE) exec app php artisan migrate --force

down:
	$(COMPOSE) down -v

restart:
	$(COMPOSE) restart

logs:
	$(COMPOSE) logs -f

shell:
	$(COMPOSE) exec app bash

migrate:
	$(COMPOSE) exec app php artisan migrate --force

seed:
	$(COMPOSE) exec app php artisan db:seed --force

fresh:
	$(COMPOSE) exec app php artisan migrate:fresh --force --seed

# Fix code style with Pint (Laravel preset) then run PHPStan (level 6). Requires host vendor/ — run make vendor first.
lint:
	docker run --rm \
		-v $(PWD):/app \
		-w /app \
		php:8.5-cli php vendor/bin/pint
	docker run --rm \
		-v $(PWD):/app \
		-w /app \
		php:8.5-cli php vendor/bin/phpstan analyse --memory-limit=512M

# Install dependencies (including dev) into host vendor/ for IDE support.
# Requires no local PHP or Composer — runs inside a throwaway container.
# Make skips this target if composer.json and composer.lock are unchanged.
vendor: composer.json composer.lock
	docker run --rm \
		-v $(PWD):/app \
		-w /app \
		composer:2.8 install --no-interaction --prefer-dist --ignore-platform-req=php

# Run the full test suite in an isolated, ephemeral environment.
# Destroys any leftover test containers first; the run container is removed on exit.
# Local dev containers are unaffected.
test:
	$(COMPOSE_TEST) down --remove-orphans
	$(COMPOSE_TEST) build
	$(COMPOSE_TEST) run --rm app php artisan test; \
	EXIT=$$?; \
	$(COMPOSE_TEST) down --remove-orphans; \
	exit $$EXIT
