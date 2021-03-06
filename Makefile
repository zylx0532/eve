#
# For more information on some of the magic targets, variables and flags used, see:
#  - [1] https://www.gnu.org/software/make/manual/html_node/Special-Targets.html
#  - [2] https://www.gnu.org/software/make/manual/html_node/Secondary-Expansion.html
#  - [3] https://www.gnu.org/software/make/manual/html_node/Suffix-Rules.html
#  - [4] https://www.gnu.org/software/make/manual/html_node/Options-Summary.html
#  - [5] https://www.gnu.org/software/make/manual/html_node/Special-Variables.html
#

# Ensure (intermediate) targets are deleted when an error occurred executing a recipe, see [1]
.DELETE_ON_ERROR:

# Enable a second expansion of the prerequisites, see [2]
.SECONDEXPANSION:

# Disable built-in implicit rules and variables, see [3, 4]
.SUFFIXES:
MAKEFLAGS += --no-builtin-rules
MAKEFLAGS += --no-builtin-variables

# Disable printing of directory changes, see [4]
MAKEFLAGS += --no-print-directory

# Warn about undefined variables -- useful during development of makefiles, see [4]
MAKEFLAGS += --warn-undefined-variables

# Show an auto-generated help if no target is provided, see [5]
.DEFAULT_GOAL := help

help:
	@echo
	@printf "%-20s %s\n" Target Description
	@echo
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-20s\033[0m %s\n", $$1, $$2}'
	@echo

#
# PROJECT TARGETS
#
# To learn more about automatic variables that can be used in target recipes, see:
#  https://www.gnu.org/software/make/manual/html_node/Automatic-Variables.html
#

PROJECT := eve

# Target that makes sure containers are built
CONTAINERS = $(shell find docker -name Dockerfile | sed 's/Dockerfile/.build/')

# Runtime dependencies
RUNTIME-DEPENDENCIES = traefik vendor/composer/installed.json $(CONTAINERS)

# Passed from ENV by travis-ci, but if not available use HEAD (currently checked out commit)
TRAVIS_COMMIT ?= $(shell git rev-parse HEAD)

# Take the short hash as release version
RELEASE = $(shell git rev-parse --short $(TRAVIS_COMMIT))

# Docker permissions
DOCKER_UID = $(shell id -u)
DOCKER_GID = $(shell id -g)
DOCKER_USER = $(DOCKER_UID):$(DOCKER_GID)

export DOCKER_UID
export DOCKER_GID

.PHONY: traefik-network
traefik-network:
	-docker network create traefik

.PHONY: traefik
traefik: traefik-network
traefik: ## run traefik
	@docker inspect -f {{.State.Running}} traefik &>/dev/null || docker run \
		--restart unless-stopped \
		--name traefik \
		--network traefik \
		--volume /var/run/docker.sock:/var/run/docker.sock \
		--publish 80:80 \
		--expose 8080 \
		--label traefik.port=8080 \
		--label traefik.enable=true \
		--detach \
		traefik --api --accesslog --docker --docker.domain=localhost --docker.exposedbydefault=false

.PHONY: traefik-cleanup
traefik-cleanup: ## clean up traefik
	@docker stop traefik &>/dev/null
	@docker rm traefik &>/dev/null
	@-docker network rm traefik &>/dev/null

.PHONY: traefik-restart
traefik-restart: traefik-cleanup traefik
traefik-restart: ## restart traefik

.PHONY: containers
containers: $(CONTAINERS)
containers: ## build all containers
	@touch $(CONTAINERS)

.PHONY: fg
fg: $(RUNTIME-DEPENDENCIES)
fg: ## launch the docker-compose setup (foreground)
	docker-compose --project-name $(PROJECT) up --remove-orphans --abort-on-container-exit

.PHONY: up
up: $(RUNTIME-DEPENDENCIES)
up: ## launch the docker-compose setup (background)
	docker-compose --project-name $(PROJECT) up --remove-orphans --detach

.PHONY: down
down: ## terminate the docker-compose setup
	-docker-compose --project-name $(PROJECT) down --remove-orphans

.PHONY: logs
logs: $(RUNTIME-DEPENDENCIES)
logs: ## show logs
	docker-compose --project-name $(PROJECT) logs

.PHONY: tail
tail: $(RUNTIME-DEPENDENCIES)
tail: ## tail logs
	docker-compose --project-name $(PROJECT) logs -f

.PHONY: shell
shell: export APP_ENV := dev
shell: export COMPOSER_HOME := /tmp
shell: $(RUNTIME-DEPENDENCIES)
shell: ## spawn a shell inside a php-fpm container
	docker-compose --project-name $(PROJECT) run --rm -e APP_ENV -e COMPOSER_HOME --user $(DOCKER_USER) --name pastebin-shell php-fpm sh

#
# PATH BASED TARGETS
#

docker/%/.build: $$(shell find $$(@D) -type f -not -name .build)
	docker-compose build $*
	@touch $@

var/cache:
	mkdir -p $@

var/log:
	mkdir -p $@

vendor:
	mkdir -p $@

vendor/composer/installed.json: export APP_ENV := dev
vendor/composer/installed.json: export COMPOSER_HOME := /tmp
vendor/composer/installed.json: composer.json composer.lock vendor var/cache var/log $(CONTAINERS)
	docker run --rm \
		--interactive \
		--env APP_ENV \
		--env COMPOSER_HOME \
		--user $(DOCKER_USER) \
		--volume /etc/passwd:/etc/passwd:ro \
		--volume /etc/group:/etc/group:ro \
		--volume $(shell pwd):/app \
		--workdir /app \
		--name pastebin-composer \
		composer install --no-interaction --no-progress --no-suggest --prefer-dist
	@touch $@
