DOCKER_COMPOSE = docker-compose --env-file .env.local
PHP = $(DOCKER_COMPOSE) exec php
COMPOSER = $(PHP) composer
CONSOLE = $(PHP) php bin/console

.PHONY: help up start stop down restart logs clean \
        install update migration migrate fixtures \
        test test-unit test-coverage \
        phpcs phpcbf pcs-fixer-dry pcs-fixer-fix psalm \
        code-style code-style-fix code-analysis \
        dev-setup db-reset cache-clear

help:
	@echo "Available commands:"
	@echo ""
	@echo "Docker commands:"
	@echo "  up        - Start containers in background"
	@echo "  start     - Start existing containers"
	@echo "  stop      - Stop containers"
	@echo "  down      - Stop and remove containers"
	@echo "  restart   - Restart containers"
	@echo "  logs      - Show container logs"
	@echo "  clean     - Clean up containers and cache"
	@echo ""
	@echo "Development:"
	@echo "  install   - Install composer dependencies"
	@echo "  update    - Update composer dependencies"
	@echo "  migration - Create new database migration"
	@echo "  migrate   - Run database migrations"
	@echo "  fixtures  - Load fixtures"
	@echo "  dev-setup - Setup development environment"
	@echo ""
	@echo "Testing:"
	@echo "  test        - Run all tests"
	@echo "  test-unit   - Run unit tests"
	@echo "  test-coverage - Run tests with coverage"
	@echo ""
	@echo "Code Quality:"
	@echo "  phpcs         - Check code standards"
	@echo "  phpcbf        - Fix code standards"
	@echo "  pcs-fixer-dry - Check code style (dry run)"
	@echo "  pcs-fixer-fix - Fix code style"
	@echo "  psalm         - Run static analysis"
	@echo ""
	@echo "Combined:"
	@echo "  code-style    - Run all code style checks"
	@echo "  code-style-fix - Fix all code style issues"
	@echo "  code-analysis - Run full code analysis"
	@echo "  pre-commit    - Run checks before commit"

# Docker commands
up:
	$(DOCKER_COMPOSE) up -d

start:
	$(DOCKER_COMPOSE) start

stop:
	$(DOCKER_COMPOSE) stop

down:
	$(DOCKER_COMPOSE) down

restart: stop start

logs:
	$(DOCKER_COMPOSE) logs -f

clean: down
	rm -rf var/cache/* var/log/* .phpcs-cache .php-cs-fixer.cache var/cache/.psalm-cache

# Development commands
install:
	$(COMPOSER) install

update:
	$(COMPOSER) update

migration:
	$(CONSOLE) make:migration

migrate:
	$(CONSOLE) doctrine:migrations:migrate --no-interaction

fixtures:
	$(CONSOLE) doctrine:fixtures:load --no-interaction

# Setup development environment
dev-setup: up install migrate
	@echo "Development environment is ready!"
	@echo "API available at: http://localhost:8080"
	@echo "Database available at: localhost:5433"

# Database
db-reset:
	$(CONSOLE) doctrine:database:drop --if-exists --force
	$(CONSOLE) doctrine:database:create
	$(CONSOLE) doctrine:migrations:migrate --no-interaction

db-reset-fixtures: db-reset fixtures

# Cache
cache-clear:
	$(CONSOLE) cache:clear

# Testing commands
test:
	$(PHP) ./vendor/bin/phpunit

test-unit:
	$(PHP) ./vendor/bin/phpunit --testsuite=unit

test-coverage:
	$(PHP) ./vendor/bin/phpunit --coverage-html var/coverage

# Code quality commands
phpcs:
	php ./vendor/bin/phpcs --standard=PSR12 src/ tests/

phpcbf:
	php ./vendor/bin/phpcbf --standard=PSR12 src/ tests/

pcs-fixer-dry:
	php ./vendor/bin/php-cs-fixer fix --dry-run --diff

pcs-fixer-fix:
	php ./vendor/bin/php-cs-fixer fix

psalm:
	php ./vendor/bin/psalm

# Combined quality commands
code-style: phpcs pcs-fixer-dry

code-style-fix: phpcbf pcs-fixer-fix

code-analysis: code-style psalm

# Pre-commit check
pre-commit: code-style psalm test-unit
	@echo "🟢 All pre-commit checks passed!"