SHELL = /bin/bash
### Standalone demo — everything runs through `make` (php/nginx in Docker).
### Config: committed defaults in env.dist, host overrides in gitignored .env.local.

include .env
-include .env.local
export

TTY  ?= $(shell if [ -t 0 ]; then echo "-it"; else echo "-T"; fi)
PUID := $(shell id -u)
PGID := $(shell id -g)

# Host identity for the logging pipeline (GELF host/hostname). HOST_NAME comes from
# env.dist/.env.local; HOST_IP is the host's first external IP, computed here.
HOST_IP := $(shell hostname -I 2>/dev/null | awk '{print $$1}' || echo unknown)
HOST_NAME := $(shell hostname)

dc       := docker compose
php      := $(dc) exec --user $(PUID):$(PGID) $(TTY) php
php-root := $(dc) exec $(TTY) php
composer := $(php) composer

# Widget bundle: pulled from the published @xakki/file-uploader npm package. The UMD
# is npm-only (export-ignored from the Composer/Packagist package), so we fetch it via
# `npm pack`. Pin the version here; the bundle is NOT committed (gitignored) and is
# refreshed by `make vendor-umd`.
FU_VERSION ?= 0.3.1
NODE_IMAGE ?= node:22-alpine

.DEFAULT_GOAL := help
.PHONY: help info create-local-files vendor-umd up s3-up down restart logs logs-follow ps config pull bash bash-root install composer-update perm-fix log-test lint smoke gc

# One-off PHP for lint/smoke (no compose stack needed). 8.4+ to satisfy locked deps.
PHP_LINT_IMAGE ?= php:8.4-cli

# One-off Composer (no compose stack needed) — for bootstrap and lock regeneration.
COMPOSER_ONEOFF ?= docker run --rm --user $(PUID):$(PGID) \
	-e COMPOSER_HOME=/tmp/c -e COMPOSER_CACHE_DIR=/tmp/cc \
	-v "$(CURDIR)":/app -w /app composer:2

##@ Help
help:  ## Display this help
	@awk 'BEGIN {FS = ":.*##"; printf "\nUsage:\n  make \033[36m<target>\033[0m\n"} /^[a-zA-Z0-9_-]+:.*?##/ { printf "  \033[36m%-18s\033[0m %s\n", $$1, $$2 } /^##@/ { printf "\n\033[1m%s\033[0m\n", substr($$0, 5) }' $(MAKEFILE_LIST)

info:  ## Project info
	@echo
	@echo "⚡ APP_URL : $(APP_URL)"
	@echo "⚡ nginx   : http://$(EXT_NGINX_PORT)"
	@echo "⚡ STORAGE : $(STORAGE)"
	@echo

##@ QA
lint:  ## php -l over src/ + bin/ + public/ (one-off php container)
	docker run --rm -v "$(CURDIR)":/app -w /app $(PHP_LINT_IMAGE) \
		sh -c 'find src bin public -name "*.php" -print0 | xargs -0 -n1 -P4 php -l'

smoke:  ## Run the per-session isolation smoke test (one-off php container)
	docker run --rm -v "$(CURDIR)":/app -w /app $(PHP_LINT_IMAGE) php tests/smoke.php

##@ Init
create-local-files:  ## Materialize .env.local from env.local.dist (first run)
	test -f .env.local || cp env.local.dist .env.local

vendor-umd:  ## Fetch the widget UMD from the published @xakki/file-uploader npm package into public/
	docker run --rm --user $(PUID):$(PGID) -e HOME=/tmp -e npm_config_cache=/tmp/.npm \
		-v "$(CURDIR)":/app -w /tmp $(NODE_IMAGE) sh -lc '\
		npm pack @xakki/file-uploader@$(FU_VERSION) >/dev/null && \
		tar -xzf xakki-file-uploader-$(FU_VERSION).tgz && \
		cp package/dist/file-uploader.umd.global.js /app/public/file-uploader.umd.js'
	@echo "UMD ← @xakki/file-uploader@$(FU_VERSION) (npm)"

##@ Dockers
up:  ## Start nginx + php (local storage)
	@$(MAKE) create-local-files --no-print-directory
	@$(MAKE) vendor-umd --no-print-directory
	$(dc) up -d
	@$(MAKE) info --no-print-directory

s3-up:  ## Start with the MinIO (s3) overlay (layered on top of the env COMPOSE_FILE, incl. logging)
	@$(MAKE) create-local-files --no-print-directory
	@$(MAKE) vendor-umd --no-print-directory
	COMPOSE_FILE=$(COMPOSE_FILE):s3.yml $(dc) up -d
	@COMPOSE_FILE=$(COMPOSE_FILE):s3.yml $(MAKE) info --no-print-directory

down:  ## Stop and remove containers
	$(dc) down

restart:  ## Recreate container(s), reload bind-mounted configs (optional `name`)
	$(dc) up -d --force-recreate --remove-orphans --no-deps $(name)

logs:  ## Last 200 log lines (optional `name`=php|nginx)
	$(dc) logs --tail=200 $(name)

logs-follow:  ## Follow logs (optional `name`)
	$(dc) logs --tail=20 --follow $(name)

ps:  ## Container status
	$(dc) ps

config:  ## Validate merged compose config
	$(dc) config -q && echo "compose config OK"

pull:  ## Pull the php + nginx images
	docker pull $(DOCKER_IMAGE_PHP)
	docker pull $(DOCKER_IMAGE_NGINX)

##@ Console
bash:  ## Shell in the php container (as host user)
	$(php) bash

bash-root:  ## Shell in the php container as root
	$(php-root) bash

##@ App
install:  ## composer update (regenerates lock from Packagist)
	$(composer) update

composer-update:  ## Regenerate composer.lock + vendor from Packagist (one-off, no stack)
	$(COMPOSER_ONEOFF) update --with-all-dependencies --no-interaction

perm-fix:  ## chown /app (vendor, storage) back to the host user
	$(dc) run --rm --no-deps --user 0 --entrypoint sh php -lc 'chown -R $(PUID):$(PGID) /app'

gc:  ## Run the garbage collector once in the running stack (expire guest files)
	$(dc) exec --user $(PUID):$(PGID) $(TTY) php php /app/bin/gc.php

##@ Logging
log-test:  ## Emit one structured JSON log line through the php container (→ stderr → fluent-bit)
	$(php) php -r 'require "/app/vendor/autoload.php"; (new Demo\Log\JsonStderrLogger("file-uploader-demo"))->info("log-test from make", ["source" => "make log-test", "ts" => time()]);'
