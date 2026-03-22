# Makefile para Ursol CAST API
# Comandos útiles para gestionar contenedores Docker

.PHONY: help build up down restart logs shell composer artisan test migrate fresh seed

# Colores para output
GREEN  := \033[0;32m
YELLOW := \033[0;33m
RED    := \033[0;31m
NC     := \033[0m # No Color

help: ## Mostrar esta ayuda
	@echo "$(GREEN)Ursol CAST API - Comandos Docker$(NC)"
	@echo ""
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "$(YELLOW)%-20s$(NC) %s\n", $$1, $$2}'

# === CI/CD ===

ci-test: ## Ejecutar tests como en CI (Docker con MySQL)
	@echo "$(GREEN)Ejecutando tests (modo CI)...$(NC)"
	docker exec ursol_php vendor/bin/phpunit --configuration=docker/phpunit.docker.xml --stop-on-failure --coverage-text

ci-quality: ## Verificar calidad de código
	@echo "$(GREEN)Verificando calidad de código...$(NC)"
	@make phpstan
	@make cs-check

ci-security: ## Verificar seguridad
	@echo "$(GREEN)Verificando seguridad...$(NC)"
	docker exec ursol_php composer audit

deploy-staging: ## Deploy a staging
	@echo "$(GREEN)Desplegando a staging...$(NC)"
	./scripts/deploy.sh staging

deploy-prod: ## Deploy a producción
	@echo "$(YELLOW)Desplegando a producción...$(NC)"
	./scripts/deploy.sh production

rollback: ## Rollback a versión anterior
	@echo "$(RED)Iniciando rollback...$(NC)"
	./scripts/rollback.sh production

# === DOCKER ===

build: ## Construir contenedores
	@echo "$(GREEN)Construyendo contenedores...$(NC)"
	docker-compose build

up: ## Iniciar contenedores
	@echo "$(GREEN)Iniciando contenedores...$(NC)"
	docker-compose up -d
	@echo "$(GREEN)✓ API disponible en: http://localhost:8000$(NC)"
	@echo "$(GREEN)✓ Swagger: http://localhost:8000/api/documentation$(NC)"
	@echo "$(GREEN)✓ PHPMyAdmin: http://localhost:8080$(NC)"

down: ## Detener contenedores
	@echo "$(YELLOW)Deteniendo contenedores...$(NC)"
	docker-compose down

restart: ## Reiniciar contenedores
	@make down
	@make up

logs: ## Ver logs de todos los contenedores
	docker-compose logs -f

logs-php: ## Ver logs de PHP
	docker-compose logs -f php

logs-nginx: ## Ver logs de Nginx
	docker-compose logs -f nginx

logs-mysql: ## Ver logs de MySQL
	docker-compose logs -f mysql

ps: ## Ver estado de contenedores
	docker-compose ps

# === SHELL ===

shell: ## Acceder a shell de PHP
	docker-compose exec php sh

shell-mysql: ## Acceder a shell de MySQL
	docker-compose exec mysql mysql -u ursol_user -pursol_password api_db

shell-redis: ## Acceder a shell de Redis
	docker-compose exec redis redis-cli

# === COMPOSER ===

composer-install: ## Instalar dependencias de Composer
	docker-compose exec php composer install

composer-update: ## Actualizar dependencias de Composer
	docker-compose exec php composer update

composer-dump: ## Generar autoload
	docker-compose exec php composer dump-autoload

# === ARTISAN ===

artisan: ## Ejecutar comando artisan (usar: make artisan CMD="route:list")
	docker-compose exec php php artisan $(CMD)

migrate: ## Ejecutar migraciones
	@echo "$(GREEN)Ejecutando migraciones...$(NC)"
	docker-compose exec php php artisan migrate

migrate-fresh: ## Resetear base de datos y ejecutar migraciones
	@echo "$(RED)¡ADVERTENCIA! Esto eliminará todos los datos$(NC)"
	@read -p "¿Continuar? [y/N]: " confirm && [ "$$confirm" = "y" ]
	docker-compose exec php php artisan migrate:fresh

seed: ## Ejecutar seeders
	@echo "$(GREEN)Ejecutando seeders...$(NC)"
	docker-compose exec php php artisan db:seed

fresh: ## Resetear BD, migrar y seedear
	@echo "$(RED)¡ADVERTENCIA! Esto eliminará todos los datos$(NC)"
	@read -p "¿Continuar? [y/N]: " confirm && [ "$$confirm" = "y" ]
	docker-compose exec php php artisan migrate:fresh --seed

# === TESTING ===

test: ## Ejecutar tests en Docker con MySQL
	docker-compose exec php php artisan test --configuration=docker/phpunit.docker.xml

test-local: ## Ejecutar tests localmente con SQLite (sin Docker)
	php artisan test

test-filter: ## Ejecutar tests filtrados (usar: make test-filter FILTER="AuthTest")
	docker-compose exec php php artisan test --filter=$(FILTER)

test-coverage: ## Ejecutar tests con cobertura
	docker-compose exec php php artisan test --coverage

# === MUTATION TESTING ===

mutation-test: ## Ejecutar mutation testing completo (requiere pcov)
	@echo "$(GREEN)Ejecutando mutation testing...$(NC)"
	vendor/bin/infection --threads=4 --show-mutations --min-msi=50 --min-covered-msi=70

mutation-test-quick: ## Mutation testing solo en cambios git (diff)
	@echo "$(GREEN)Ejecutando mutation testing incremental...$(NC)"
	vendor/bin/infection --threads=4 --git-diff-lines --git-diff-base=main --min-msi=0 --min-covered-msi=0 --show-mutations --ignore-msi-with-no-mutations

mutation-test-filter: ## Mutation testing filtrado (usar: make mutation-test-filter FILTER="ClaveNumerica")
	@echo "$(GREEN)Ejecutando mutation testing para $(FILTER)...$(NC)"
	vendor/bin/infection --threads=4 --filter="$(FILTER)" --show-mutations --min-msi=0 --min-covered-msi=0

mutation-test-services: ## Mutation testing solo de Services
	@echo "$(GREEN)Ejecutando mutation testing de servicios...$(NC)"
	vendor/bin/infection --threads=4 --filter="app/Services" --show-mutations --min-msi=50 --min-covered-msi=70

mutation-test-rules: ## Mutation testing solo de Rules (CrTelefono, CrIdentificacion)
	@echo "$(GREEN)Ejecutando mutation testing de reglas de validación...$(NC)"
	vendor/bin/infection --threads=4 --filter="app/Rules" --show-mutations --min-msi=80 --min-covered-msi=90

# === CONTRACT TESTING ===

contract-test: ## Ejecutar todos los contract tests (consumer + verificación)
	@echo "$(GREEN)Ejecutando contract tests...$(NC)"
	php vendor/bin/phpunit --testsuite Contract-Consumer --no-coverage

contract-test-consumer: ## Ejecutar solo consumer contract tests (genera pacts)
	@echo "$(GREEN)Ejecutando consumer contract tests...$(NC)"
	php vendor/bin/phpunit --testsuite Contract-Consumer --no-coverage

contract-test-provider: ## Verificar contratos contra el provider real
	@echo "$(GREEN)Ejecutando provider contract verification...$(NC)"
	php vendor/bin/phpunit --testsuite Contract-Provider --no-coverage

# === CACHE ===

cache-clear: ## Limpiar todos los cachés
	@echo "$(GREEN)Limpiando cachés...$(NC)"
	docker-compose exec php php artisan cache:clear
	docker-compose exec php php artisan config:clear
	docker-compose exec php php artisan route:clear
	docker-compose exec php php artisan view:clear

optimize: ## Optimizar aplicación (producción)
	@echo "$(GREEN)Optimizando aplicación...$(NC)"
	docker-compose exec php php artisan config:cache
	docker-compose exec php php artisan route:cache
	docker-compose exec php php artisan view:cache
	docker-compose exec php composer dump-autoload -o

# === SWAGGER ===

swagger: ## Regenerar documentación Swagger
	@echo "$(GREEN)Regenerando Swagger...$(NC)"
	docker-compose exec php php artisan l5-swagger:generate

# === DESARROLLO ===

dev: ## Iniciar entorno completo de desarrollo
	@echo "$(GREEN)Iniciando entorno de desarrollo...$(NC)"
	docker-compose -f docker-compose.yml -f docker-compose.dev.yml up -d
	@echo "$(GREEN)✓ API: http://localhost:8000$(NC)"
	@echo "$(GREEN)✓ Swagger: http://localhost:8000/api/documentation$(NC)"
	@echo "$(GREEN)✓ PHPMyAdmin: http://localhost:8080$(NC)"
	@echo "$(GREEN)✓ Mailhog: http://localhost:8025$(NC)"

dev-down: ## Detener entorno de desarrollo
	docker-compose -f docker-compose.yml -f docker-compose.dev.yml down

# === INSTALACIÓN INICIAL ===

install: ## Instalación inicial completa
	@echo "$(GREEN)Instalación inicial de Ursol CAST API$(NC)"
	@make build
	@make up
	@echo "$(YELLOW)Esperando que MySQL esté listo...$(NC)"
	@sleep 10
	@make composer-install
	@echo "$(GREEN)Generando APP_KEY...$(NC)"
	@docker-compose exec php php artisan key:generate
	@make migrate
	@make seed
	@make swagger
	@echo "$(GREEN)✓ ¡Instalación completada!$(NC)"
	@echo "$(GREEN)✓ API: http://localhost:8000$(NC)"
	@echo "$(GREEN)✓ Swagger: http://localhost:8000/api/documentation$(NC)"
	@echo "$(GREEN)✓ Credenciales: admin@ursol.com / admin123$(NC)"

# === LIMPIEZA ===

clean: ## Limpiar contenedores y volúmenes
	@echo "$(RED)¡ADVERTENCIA! Esto eliminará contenedores y volúmenes$(NC)"
	@read -p "¿Continuar? [y/N]: " confirm && [ "$$confirm" = "y" ]
	docker-compose down -v
	@echo "$(GREEN)✓ Limpieza completada$(NC)"

# === PRODUCCIÓN ===

prod-up: ## Iniciar en modo producción
	@echo "$(GREEN)Iniciando en modo producción...$(NC)"
	docker-compose --profile production up -d
	@make optimize

prod-down: ## Detener modo producción
	docker-compose --profile production down

# === BACKUP ===

backup-db: ## Backup de base de datos
	@echo "$(GREEN)Creando backup de base de datos...$(NC)"
	docker-compose exec mysql mysqldump -u ursol_user -pursol_password api_db > backup_$(shell date +%Y%m%d_%H%M%S).sql
	@echo "$(GREEN)✓ Backup creado$(NC)"

# === INFO ===

status: ## Ver estado completo del sistema
	@echo "$(GREEN)Estado de Ursol CAST API$(NC)"
	@echo ""
	@echo "$(YELLOW)Contenedores:$(NC)"
	@docker-compose ps
	@echo ""
	@echo "$(YELLOW)URLs disponibles:$(NC)"
	@echo "API:        http://localhost:8000"
	@echo "Swagger:    http://localhost:8000/api/documentation"
	@echo "PHPMyAdmin: http://localhost:8080"
	@echo "Mailhog:    http://localhost:8025"
