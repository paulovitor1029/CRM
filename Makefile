SHELL := /bin/bash

DOCKER_COMPOSE := docker compose
PHP := $(DOCKER_COMPOSE) exec -T php
ARTISAN := $(PHP) php artisan

.PHONY: init build up down ps logs bash composer-install key migrate seed test lint coverage apply-stubs refresh-db

init:
	@if [ ! -f .env ]; then cp .env.example .env; fi
	@if [ ! -f .env.secrets ]; then cp .env.secrets.example .env.secrets; fi

build:
	$(DOCKER_COMPOSE) build --pull

up:
	$(DOCKER_COMPOSE) up -d --remove-orphans

down:
	$(DOCKER_COMPOSE) down --remove-orphans -v

ps:
	$(DOCKER_COMPOSE) ps

logs:
	$(DOCKER_COMPOSE) logs -f

bash:
	$(DOCKER_COMPOSE) exec php bash

composer-install:
	$(PHP) composer install --no-interaction --prefer-dist --optimize-autoloader

key:
	$(ARTISAN) key:generate

migrate:
	$(ARTISAN) migrate --force

seed:
	$(ARTISAN) db:seed --force

test:
	$(PHP) ./vendor/bin/pest --coverage --min=80

lint:
	$(PHP) ./vendor/bin/pint -v --test || true
	$(PHP) ./vendor/bin/phpstan analyse --memory-limit=1G

coverage:
	$(PHP) ./vendor/bin/pest --coverage --min=80 --coverage-clover=coverage/clover.xml

refresh-db:
	$(ARTISAN) migrate:fresh --seed --force

apply-stubs:
	./scripts/apply-stubs.sh
