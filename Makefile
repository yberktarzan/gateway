COMPOSE ?= compose.local.yml

.PHONY: help net-up up down logs sh artisan composer queue-restart

help: ## Show this help message
	@echo 'Usage: make [target]'
	@echo ''
	@echo 'Targets:'
	@awk 'BEGIN {FS = ":.*?## "} /^[a-zA-Z_-]+:.*?## / {printf "  %-15s %s\n", $$1, $$2}' $(MAKEFILE_LIST)

net-up: ## Create mesh network if it doesn't exist
	@docker network inspect ${MESH_NETWORK:-mesh} >/dev/null 2>&1 || docker network create ${MESH_NETWORK:-mesh}

up: net-up ## Start services
	docker compose -f $(COMPOSE) --env-file .env up -d --build

down: ## Stop services
	docker compose -f $(COMPOSE) --env-file .env down

logs: ## Show logs
	docker compose -f $(COMPOSE) --env-file .env logs -f --tail=200

sh: ## Access app shell
	docker compose -f $(COMPOSE) --env-file .env exec app bash

artisan: ## Run artisan command (use: make artisan CMD="migrate")
	docker compose -f $(COMPOSE) --env-file .env exec app php artisan $(CMD)

composer: ## Run composer command (use: make composer CMD="install")
	docker compose -f $(COMPOSE) --env-file .env exec app composer $(CMD)

queue-restart: ## Restart queue workers
	docker compose -f $(COMPOSE) --env-file .env exec app php artisan queue:restart

# Production shortcuts
prod-up: ## Start production services
	$(MAKE) up COMPOSE=compose.prod.yml

prod-down: ## Stop production services
	$(MAKE) down COMPOSE=compose.prod.yml

prod-logs: ## Show production logs
	$(MAKE) logs COMPOSE=compose.prod.yml
