#!/bin/bash

#############################################
# Rollback Script para Senselab Core API
# Uso: ./scripts/rollback.sh [staging|production] [version]
#############################################

set -e
set -u

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

ENVIRONMENT=${1:-production}
VERSION=${2:-}

# Cargar variables de entorno desde .env
SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
PROJECT_ROOT="$(dirname "$SCRIPT_DIR")"
if [[ -f "${PROJECT_ROOT}/.env" ]]; then
    set -a
    # shellcheck source=/dev/null
    source "${PROJECT_ROOT}/.env"
    set +a
fi

echo -e "${RED}========================================${NC}"
echo -e "${RED}Senselab Core API - ROLLBACK Script${NC}"
echo -e "${RED}Environment: $ENVIRONMENT${NC}"
echo -e "${RED}========================================${NC}"

# Validar argumentos
if [[ "$ENVIRONMENT" != "staging" && "$ENVIRONMENT" != "production" ]]; then
    echo -e "${RED}Error: Environment must be 'staging' or 'production'${NC}"
    exit 1
fi

# Confirmación
echo -e "${YELLOW}⚠️  WARNING: This will ROLLBACK the application!${NC}"
read -r -p "Are you sure? (type 'yes' to continue): " ROLLBACK_CONFIRM
if [[ ! $ROLLBACK_CONFIRM =~ ^yes$ ]]; then
    echo -e "${GREEN}Rollback cancelled${NC}"
    exit 0
fi

# Poner en mantenimiento
echo -e "\n${YELLOW}[1/6] Enabling maintenance mode...${NC}"
docker-compose exec -T php php artisan down

# Listar backups disponibles
echo -e "\n${YELLOW}[2/6] Available backups:${NC}"
ls -lh /backups/ | tail -10

# Seleccionar backup
if [[ -z "$VERSION" ]]; then
    echo -e "\nEnter backup directory name (or press Enter for latest):"
    read -r BACKUP_DIR
    
    if [[ -z "$BACKUP_DIR" ]]; then
        BACKUP_DIR=$(ls -t /backups/ | head -1)
    fi
else
    BACKUP_DIR="$VERSION"
fi

BACKUP_PATH="/backups/$BACKUP_DIR"

if [[ ! -d "$BACKUP_PATH" ]]; then
    echo -e "${RED}Error: Backup directory not found: $BACKUP_PATH${NC}"
    exit 1
fi

echo -e "${GREEN}Using backup: $BACKUP_PATH${NC}"

# Rollback código
echo -e "\n${YELLOW}[3/6] Rolling back code...${NC}"
if [[ -n "$VERSION" ]]; then
    git reset --hard "$VERSION"
else
    echo -e "${YELLOW}Warning: Reverting to HEAD~1. Consider specifying a version.${NC}"
    git reset --hard HEAD~1
fi

# Rebuild containers
echo -e "\n${YELLOW}[4/6] Rebuilding containers...${NC}"
docker-compose down
docker-compose up -d --build

sleep 10

# Rollback database
echo -e "\n${YELLOW}[5/6] Rolling back database...${NC}"
if [[ -f "$BACKUP_PATH/database.sql" ]]; then
    docker-compose exec -T mysql mysql \
        -u root \
        -p"${MYSQL_ROOT_PASSWORD}" \
        "${DB_DATABASE}" < "$BACKUP_PATH/database.sql"
    echo -e "${GREEN}✓ Database restored${NC}"
else
    echo -e "${RED}Warning: No database backup found${NC}"
fi

# Restore files
if [[ -f "$BACKUP_PATH/storage.tar.gz" ]]; then
    tar -xzf "$BACKUP_PATH/storage.tar.gz"
    echo -e "${GREEN}✓ Storage files restored${NC}"
fi

# Clear caches y optimizar
docker-compose exec -T php php artisan cache:clear
docker-compose exec -T php php artisan config:cache
docker-compose exec -T php php artisan route:cache

# Salir de mantenimiento
echo -e "\n${YELLOW}[6/6] Disabling maintenance mode...${NC}"
docker-compose exec -T php php artisan up

# Verificar
echo -e "\n${GREEN}Verifying rollback...${NC}"
HTTP_STATUS=$(curl -s -o /dev/null -w "%{http_code}" "http://localhost:8000/health" 2>/dev/null || echo "000")
if [ "$HTTP_STATUS" -eq 200 ]; then
    echo -e "${GREEN}✓ Rollback successful!${NC}"
else
    echo -e "${RED}✗ Health check failed (HTTP $HTTP_STATUS)${NC}"
fi

echo -e "\n${GREEN}========================================${NC}"
echo -e "${GREEN}Rollback completed${NC}"
echo -e "${GREEN}========================================${NC}"

exit 0
