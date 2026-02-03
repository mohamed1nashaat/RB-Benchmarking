.PHONY: help up down build install migrate seed test clean logs

# Default target
help:
	@echo "Available commands:"
	@echo "  make up       - Start all services"
	@echo "  make down     - Stop all services"
	@echo "  make build    - Build Docker containers"
	@echo "  make install  - Install dependencies"
	@echo "  make migrate  - Run database migrations"
	@echo "  make seed     - Seed database with demo data"
	@echo "  make test     - Run tests"
	@echo "  make clean    - Clean up containers and volumes"
	@echo "  make logs     - Show logs"

# Start all services
up:
	docker-compose up -d

# Stop all services
down:
	docker-compose down

# Build containers
build:
	docker-compose build

# Install dependencies
install:
	docker-compose exec app composer install
	docker-compose exec node npm install

# Run migrations
migrate:
	docker-compose exec app php artisan migrate

# Seed database
seed:
	docker-compose exec app php artisan db:seed

# Run tests
test:
	docker-compose exec app php artisan test

# Clean up
clean:
	docker-compose down -v
	docker system prune -f

# Show logs
logs:
	docker-compose logs -f

# Setup project (first time)
setup: build up install migrate seed
	@echo "Project setup complete!"
	@echo "Access the application at: https://rb-benchmarks.redbananas.com"
	@echo "Demo credentials:"
	@echo "  Admin: admin@demo.com / password"
	@echo "  Viewer: viewer@demo.com / password"

# Development commands
dev-backend:
	docker-compose exec app php artisan serve --host=0.0.0.0 --port=8000

dev-frontend:
	docker-compose exec node npm run dev

# Database commands
fresh:
	docker-compose exec app php artisan migrate:fresh --seed

# Cache commands
cache-clear:
	docker-compose exec app php artisan cache:clear
	docker-compose exec app php artisan config:clear
	docker-compose exec app php artisan route:clear
	docker-compose exec app php artisan view:clear

# Generate application key
key:
	docker-compose exec app php artisan key:generate

# Run specific tests
test-unit:
	docker-compose exec app php artisan test --testsuite=Unit

test-feature:
	docker-compose exec app php artisan test --testsuite=Feature

# Code quality
lint:
	docker-compose exec app ./vendor/bin/pint

analyze:
	docker-compose exec app ./vendor/bin/phpstan analyse

# Production commands
prod-build:
	docker-compose -f docker-compose.prod.yml build

prod-up:
	docker-compose -f docker-compose.prod.yml up -d

prod-down:
	docker-compose -f docker-compose.prod.yml down
