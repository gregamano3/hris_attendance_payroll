.DEFAULT_GOAL := help
DC := docker compose
APP := $(DC) exec app

help: ## Show available commands
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}; {printf "  \033[36m%-12s\033[0m %s\n", $$1, $$2}'

setup: ## First-time setup: env, images, dependencies, database
	@test -f .env || (cp .env.example .env && sed -i "s/^UID=.*/UID=$$(id -u)/; s/^GID=.*/GID=$$(id -g)/" .env)
	$(DC) build
	$(DC) up -d
	$(APP) composer install
	$(APP) php artisan key:generate
	$(APP) php artisan migrate --seed
	$(MAKE) assets

assets: ## Install npm deps, build Vite assets and publish AdminLTE assets
	$(DC) run --rm --no-deps node sh -c "npm install && npm run build"
	$(APP) php artisan adminlte:install --only=assets --only=vendor_assets --force -n

up: ## Start the stack
	$(DC) up -d

down: ## Stop the stack
	$(DC) down

logs: ## Tail logs
	$(DC) logs -f

shell: ## Shell into the app container
	$(APP) sh

artisan: ## Run an artisan command: make artisan cmd="migrate"
	$(APP) php artisan $(cmd)

composer: ## Run a composer command: make composer cmd="require foo/bar"
	$(APP) composer $(cmd)

npm: ## Run an npm command: make npm cmd="run build"
	$(DC) run --rm node npm $(cmd)

fresh: ## Drop all tables, migrate and seed
	$(APP) php artisan migrate:fresh --seed

test: ## Run the Pest test suite
	$(APP) ./vendor/bin/pest

lint: ## Check code style (Pint)
	$(APP) ./vendor/bin/pint --test

fix: ## Fix code style (Pint)
	$(APP) ./vendor/bin/pint

stan: ## Static analysis (Larastan)
	$(APP) ./vendor/bin/phpstan analyse --memory-limit=1G

check: lint stan test ## Run all quality checks

# Isolated stack for end-to-end tests: separate compose project, volumes and ports.
E2E_ENV := COMPOSE_PROJECT_NAME=hris-e2e APP_PORT=8090 FORWARD_DB_PORT=54321 FORWARD_REDIS_PORT=63791 FORWARD_MAILPIT_DASHBOARD_PORT=8027 LOGIN_THROTTLE=200

e2e-up: ## Start the isolated end-to-end stack on http://localhost:8090
	$(E2E_ENV) $(DC) up -d --wait app web db redis queue

e2e-down: ## Stop the end-to-end stack and drop its data
	$(E2E_ENV) $(DC) down -v

e2e: e2e-up ## Run Playwright end-to-end tests against the isolated stack
	cd e2e && npm ci --no-audit --no-fund && npx playwright install chromium
	cd e2e && E2E_BASE_URL=http://localhost:8090 \
		E2E_RESET_CMD="$(E2E_ENV) docker compose exec -T app php artisan migrate:fresh --seed --force" \
		npx playwright test

.PHONY: help setup assets up down logs shell artisan composer npm fresh test lint fix stan check e2e e2e-up e2e-down
