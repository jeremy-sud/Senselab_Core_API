#!/bin/bash

#############################################
# Deploy Script para Ursol CAST API
# Uso: ./scripts/deploy.sh [staging|production]
#############################################

set -e  # Exit on error
set -u  # Exit on undefined variable

# Colores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Configuración
ENVIRONMENT=${1:-staging}
SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
PROJECT_ROOT="$(dirname "$SCRIPT_DIR")"

echo -e "${GREEN}========================================${NC}"
echo -e "${GREEN}Ursol CAST API - Deploy Script${NC}"
echo -e "${GREEN}Environment: $ENVIRONMENT${NC}"
echo -e "${GREEN}========================================${NC}"

# Validar argumento
if [[ "$ENVIRONMENT" != "staging" && "$ENVIRONMENT" != "production" ]]; then
    echo -e "${RED}Error: Environment must be 'staging' or 'production'${NC}"
    exit 1
fi

# Confirmación para producción
if [[ "$ENVIRONMENT" == "production" ]]; then
    echo -e "${YELLOW}⚠️  WARNING: Deploying to PRODUCTION!${NC}"
    read -p "Are you sure? (type 'yes' to continue): " -r
    if [[ ! $REPLY =~ ^yes$ ]]; then
        echo -e "${RED}Deployment cancelled${NC}"
        exit 1
    fi
fi

# Step 1: Validar pre-requisitos
echo -e "\n${GREEN}[1/8] Checking prerequisites...${NC}"
command -v docker >/dev/null 2>&1 || { echo -e "${RED}Docker is required but not installed${NC}"; exit 1; }
command -v docker-compose >/dev/null 2>&1 || { echo -e "${RED}Docker Compose is required but not installed${NC}"; exit 1; }
command -v git >/dev/null 2>&1 || { echo -e "${RED}Git is required but not installed${NC}"; exit 1; }

# Step 2: Validar branch
echo -e "\n${GREEN}[2/8] Validating git branch...${NC}"
CURRENT_BRANCH=$(git rev-parse --abbrev-ref HEAD)
if [[ "$ENVIRONMENT" == "production" && "$CURRENT_BRANCH" != "main" ]]; then
    echo -e "${RED}Error: Production deploys must be from 'main' branch${NC}"
    exit 1
fi
if [[ "$ENVIRONMENT" == "staging" && "$CURRENT_BRANCH" != "develop" ]]; then
    echo -e "${YELLOW}Warning: Staging deploys are typically from 'develop' branch${NC}"
    echo -e "Current branch: $CURRENT_BRANCH"
    read -p "Continue anyway? (y/n): " -r
    [[ ! $REPLY =~ ^[Yy]$ ]] && exit 1
fi

# Step 3: Pull latest changes
echo -e "\n${GREEN}[3/8] Pulling latest changes...${NC}"
git pull origin "$CURRENT_BRANCH"

# Step 4: Run tests
echo -e "\n${GREEN}[4/8] Running test suite...${NC}"
docker-compose exec -T php php artisan test --stop-on-failure
if [ $? -ne 0 ]; then
    echo -e "${RED}Tests failed! Aborting deployment.${NC}"
    exit 1
fi

# Step 5: Backup (only production)
if [[ "$ENVIRONMENT" == "production" ]]; then
    echo -e "\n${GREEN}[5/8] Creating backup...${NC}"
    BACKUP_DIR="/backups/$(date +%Y%m%d_%H%M%S)"
    mkdir -p "$BACKUP_DIR"
    
    # Database backup
    docker-compose exec -T mysql mysqldump \
        -u root \
        -p"${MYSQL_ROOT_PASSWORD}" \
        "${DB_DATABASE}" > "$BACKUP_DIR/database.sql"
    
    # Files backup
    tar -czf "$BACKUP_DIR/storage.tar.gz" storage/
    
    echo -e "${GREEN}Backup created at: $BACKUP_DIR${NC}"
else
    echo -e "\n${GREEN}[5/8] Skipping backup (staging environment)${NC}"
fi

# Step 6: Build containers
echo -e "\n${GREEN}[6/8] Building Docker containers...${NC}"
if [[ "$ENVIRONMENT" == "production" ]]; then
    docker-compose -f docker-compose.yml build --no-cache
else
    docker-compose -f docker-compose.staging.yml build --no-cache
fi

# Step 7: Deploy
echo -e "\n${GREEN}[7/8] Deploying application...${NC}"

# Poner en mantenimiento (solo producción)
if [[ "$ENVIRONMENT" == "production" ]]; then
    docker-compose exec -T php php artisan down --retry=60 --secret="deploy-secret-key"
fi

# Stop containers
if [[ "$ENVIRONMENT" == "production" ]]; then
    docker-compose down
else
    docker-compose -f docker-compose.staging.yml down
fi

# Start new containers
if [[ "$ENVIRONMENT" == "production" ]]; then
    docker-compose up -d
else
    docker-compose -f docker-compose.staging.yml up -d
fi

# Wait for containers
echo "Waiting for containers to be ready..."
sleep 10

# Run migrations
echo "Running database migrations..."
docker-compose exec -T php php artisan migrate --force

# Clear caches
echo "Clearing caches..."
docker-compose exec -T php php artisan cache:clear
docker-compose exec -T php php artisan config:clear
docker-compose exec -T php php artisan route:clear
docker-compose exec -T php php artisan view:clear

# Optimize
echo "Optimizing application..."
docker-compose exec -T php php artisan config:cache
docker-compose exec -T php php artisan route:cache
docker-compose exec -T php php artisan view:cache

# Salir de mantenimiento (solo producción)
if [[ "$ENVIRONMENT" == "production" ]]; then
    docker-compose exec -T php php artisan up
fi

# Step 8: Smoke tests
echo -e "\n${GREEN}[8/8] Running smoke tests...${NC}"

# Health check
if [[ "$ENVIRONMENT" == "production" ]]; then
    HEALTH_URL="https://api.ursol-cast.com/api/health"
else
    HEALTH_URL="http://localhost:8080/api/health"
fi

sleep 5
HTTP_STATUS=$(curl -s -o /dev/null -w "%{http_code}" "$HEALTH_URL")
if [ "$HTTP_STATUS" -eq 200 ]; then
    echo -e "${GREEN}✓ Health check passed${NC}"
else
    echo -e "${RED}✗ Health check failed (HTTP $HTTP_STATUS)${NC}"
    
    if [[ "$ENVIRONMENT" == "production" ]]; then
        echo -e "${RED}Rolling back...${NC}"
        # Rollback logic aquí
        exit 1
    fi
fi

# Success!
echo -e "\n${GREEN}========================================${NC}"
echo -e "${GREEN}✓ Deployment to $ENVIRONMENT completed successfully!${NC}"
echo -e "${GREEN}========================================${NC}"

# Next steps
echo -e "\n${YELLOW}Next steps:${NC}"
if [[ "$ENVIRONMENT" == "staging" ]]; then
    echo "  1. Test at: http://staging.ursol-cast.com"
    echo "  2. Run E2E tests"
    echo "  3. Get QA approval"
else
    echo "  1. Monitor logs: docker-compose logs -f"
    echo "  2. Check monitoring dashboard"
    echo "  3. Notify team of deployment"
fi

exit 0
