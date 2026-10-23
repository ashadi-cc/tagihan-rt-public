.PHONY: help build up down restart logs bash bash-root composer npm artisan migrate fresh seed test test-db clean

help: ## Show this help message
	@echo 'Usage: make [target]'
	@echo ''
	@echo 'Available targets:'
	@awk 'BEGIN {FS = ":.*?## "} /^[a-zA-Z_-]+:.*?## / {printf "  \033[36m%-15s\033[0m %s\n", $$1, $$2}' $(MAKEFILE_LIST)

build: ## Build all docker images
	docker-compose build

up: ## Start all services
	docker-compose up -d

down: ## Stop all services
	docker-compose down

restart: ## Restart all services
	docker-compose restart

logs: ## Show logs from all services
	docker-compose logs -f

bash: ## Enter bash in app container as www-data user
	docker-compose exec -u www-data app bash

bash-root: ## Enter bash in app container as root
	docker-compose exec app bash

composer: ## Run composer install
	docker-compose exec -u www-data app composer install

npm-install: ## Run npm install
	docker-compose exec -u www-data app npm install

npm-dev: ## Run npm dev
	docker-compose exec -u www-data app npm run dev

npm-build: ## Run npm build
	docker-compose exec -u www-data app npm run build

artisan: ## Run artisan command (use: make artisan cmd="migrate")
	docker-compose exec -u www-data app php artisan $(cmd)

migrate: ## Run database migrations
	docker-compose exec -u www-data app php artisan migrate

fresh: ## Fresh migration with seed
	docker-compose exec -u www-data app php artisan migrate:fresh --seed

seed: ## Run database seeders
	docker-compose exec -u www-data app php artisan db:seed

test-db: ## Create the database used by the test suite (rt4_testing)
	docker-compose exec db sh -c 'mariadb -uroot -p"$$MYSQL_ROOT_PASSWORD" -e "CREATE DATABASE IF NOT EXISTS rt4_testing"'

test: test-db ## Run tests
	docker-compose exec -u www-data app php artisan test

queue-listen: ## Run queue listener
	docker-compose exec -u www-data app php artisan queue:listen

cache-clear: ## Clear all cache
	docker-compose exec -u www-data app php artisan cache:clear
	docker-compose exec -u www-data app php artisan config:clear
	docker-compose exec -u www-data app php artisan view:clear
	docker-compose exec -u www-data app php artisan route:clear

clean: ## Remove all containers, volumes, and images
	docker-compose down -v --rmi all

ps: ## Show running containers
	docker-compose ps

db-bash: ## Enter bash in database container
	docker-compose exec db bash

db-mysql: ## Enter MySQL CLI in database container
	docker-compose exec db sh -c 'mysql -uroot -p"$$MYSQL_ROOT_PASSWORD" dev-rt-4-01'

adminer: ## Open Adminer in browser (database management UI)
	@echo "Opening Adminer at http://localhost:8080"
	@echo "Server: db | Username: root | Password: (DB_PASSWORD from .env) | Database: dev-rt-4-01"