# Makefile for Customer Management Dashboard

.PHONY: help install dev build test lint security docker clean

# Default target
.DEFAULT_GOAL := help

# Variables
COMPOSER = composer
NPM = npm
ARTISAN = php artisan
DOCKER_COMPOSE = docker-compose

## Help
help: ## Show this help message
	@echo 'Usage: make <target>'
	@echo ''
	@echo 'Available targets:'
	@awk 'BEGIN {FS = ":.*?## "} /^[a-zA-Z_-]+:.*?## / {printf "  %-15s %s\n", $$1, $$2}' $(MAKEFILE_LIST)

## Installation
install: ## Install all dependencies
	$(COMPOSER) install
	$(NPM) install
	cp .env.example .env
	$(ARTISAN) key:generate
	@echo "✅ Installation complete! Don't forget to configure your .env file"

install-prod: ## Install production dependencies
	$(COMPOSER) install --no-dev --optimize-autoloader
	$(NPM) ci --production
	$(ARTISAN) config:cache
	$(ARTISAN) route:cache
	$(ARTISAN) view:cache

## Development
dev: ## Start development server
	$(NPM) run dev

watch: ## Start file watcher for assets
	$(NPM) run watch

serve: ## Start Laravel development server
	$(ARTISAN) serve

fresh: ## Fresh install with database migration
	$(ARTISAN) migrate:fresh --seed
	@echo "✅ Database refreshed with seed data"

## Building
build: ## Build production assets
	$(NPM) run build

build-dev: ## Build development assets
	$(NPM) run dev

## Database
migrate: ## Run database migrations
	$(ARTISAN) migrate

migrate-fresh: ## Fresh migration with seeding
	$(ARTISAN) migrate:fresh --seed

seed: ## Run database seeders
	$(ARTISAN) db:seed

## Testing
test: ## Run all tests
	$(ARTISAN) test

test-coverage: ## Run tests with coverage
	$(ARTISAN) test --coverage

test-unit: ## Run unit tests only
	$(ARTISAN) test --testsuite=Unit

test-feature: ## Run feature tests only
	$(ARTISAN) test --testsuite=Feature

## Code Quality
lint: ## Run code linting
	./vendor/bin/php-cs-fixer fix --dry-run --diff
	./vendor/bin/phpstan analyse
	./vendor/bin/psalm

lint-fix: ## Fix code style issues
	./vendor/bin/php-cs-fixer fix

analyze: ## Run static analysis
	./vendor/bin/phpstan analyse
	./vendor/bin/psalm

## Security
security: ## Run security checks
	$(COMPOSER) audit
	$(NPM) audit
	@echo "✅ Security audit complete"

security-fix: ## Fix security vulnerabilities
	$(COMPOSER) audit --fix
	$(NPM) audit fix

## Docker
docker-build: ## Build Docker containers
	$(DOCKER_COMPOSE) build

docker-up: ## Start Docker environment
	$(DOCKER_COMPOSE) up -d
	@echo "✅ Docker environment started"
	@echo "📱 Application: http://localhost:8080"
	@echo "📧 MailHog: http://localhost:8025"
	@echo "🗄️  phpMyAdmin: http://localhost:8081"

docker-down: ## Stop Docker environment
	$(DOCKER_COMPOSE) down

docker-logs: ## View Docker logs
	$(DOCKER_COMPOSE) logs -f

docker-shell: ## Access application container shell
	$(DOCKER_COMPOSE) exec app sh

docker-setup: ## Setup Docker environment
	cp .env.docker .env
	$(DOCKER_COMPOSE) up -d
	$(DOCKER_COMPOSE) exec app composer install
	$(DOCKER_COMPOSE) exec app php artisan key:generate
	$(DOCKER_COMPOSE) exec app php artisan migrate:fresh --seed
	$(DOCKER_COMPOSE) exec app npm install
	$(DOCKER_COMPOSE) exec app npm run build
	@echo "✅ Docker environment setup complete!"

## Cleanup
clean: ## Clean cache and temporary files
	$(ARTISAN) cache:clear
	$(ARTISAN) config:clear
	$(ARTISAN) route:clear
	$(ARTISAN) view:clear
	$(COMPOSER) clear-cache
	$(NPM) cache clean --force
	@echo "✅ Cache cleared"

clean-all: ## Clean everything including node_modules and vendor
	rm -rf node_modules/
	rm -rf vendor/
	$(NPM) cache clean --force
	$(COMPOSER) clear-cache
	@echo "✅ Full cleanup complete"

## Production
deploy: ## Deploy to production
	git pull origin main
	$(COMPOSER) install --no-dev --optimize-autoloader
	$(NPM) ci --production
	$(NPM) run build
	$(ARTISAN) migrate --force
	$(ARTISAN) config:cache
	$(ARTISAN) route:cache
	$(ARTISAN) view:cache
	$(ARTISAN) queue:restart
	@echo "✅ Deployment complete"

backup: ## Create database backup
	$(ARTISAN) backup:run
	@echo "✅ Backup created"

## Maintenance
optimize: ## Optimize application for production
	$(ARTISAN) config:cache
	$(ARTISAN) route:cache
	$(ARTISAN) view:cache
	$(ARTISAN) optimize
	@echo "✅ Application optimized"

queue-work: ## Start queue worker
	$(ARTISAN) queue:work --verbose --tries=3 --timeout=90

schedule-work: ## Start task scheduler
	$(ARTISAN) schedule:work

## Documentation
docs: ## Generate documentation
	@echo "📚 Documentation available in README.md"
	@echo "🔗 API Documentation: /docs/api"
	@echo "🔗 Security Policy: SECURITY.md"
