#!/bin/bash

# Script de Health Check para Docker
# Verifica que todos los servicios estén funcionando correctamente

set -e

echo "🏥 Verificando salud de servicios Docker..."
echo ""

# Colores
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Función para verificar servicio
check_service() {
    local service=$1
    local check_command=$2
    
    echo -n "Verificando $service... "
    
    if eval "$check_command" > /dev/null 2>&1; then
        echo -e "${GREEN}✓ OK${NC}"
        return 0
    else
        echo -e "${RED}✗ FAIL${NC}"
        return 1
    fi
}

# Verificar que Docker esté corriendo
if ! docker info > /dev/null 2>&1; then
    echo -e "${RED}✗ Docker no está corriendo${NC}"
    exit 1
fi

echo -e "${GREEN}✓ Docker está corriendo${NC}"
echo ""

# Verificar contenedores
echo "📦 Estado de contenedores:"
docker-compose ps
echo ""

# Verificar servicios individuales
ERRORS=0

# Nginx
check_service "Nginx" "curl -f http://localhost:8000/health" || ERRORS=$((ERRORS+1))

# MySQL
check_service "MySQL" "docker-compose exec -T mysql mysqladmin ping -h localhost -u root -proot_password" || ERRORS=$((ERRORS+1))

# Redis
check_service "Redis" "docker-compose exec -T redis redis-cli ping" || ERRORS=$((ERRORS+1))

# PHP-FPM
check_service "PHP-FPM" "docker-compose exec -T php php-fpm -t" || ERRORS=$((ERRORS+1))

# Laravel Application
check_service "Laravel App" "docker-compose exec -T php php artisan --version" || ERRORS=$((ERRORS+1))

# Database connectivity
check_service "DB Connection" "docker-compose exec -T php php artisan migrate:status" || ERRORS=$((ERRORS+1))

echo ""

# Resumen
if [ $ERRORS -eq 0 ]; then
    echo -e "${GREEN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    echo -e "${GREEN}✅ Todos los servicios están saludables${NC}"
    echo -e "${GREEN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    exit 0
else
    echo -e "${RED}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    echo -e "${RED}❌ $ERRORS servicio(s) con problemas${NC}"
    echo -e "${RED}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    echo ""
    echo -e "${YELLOW}Revisar logs con: make logs${NC}"
    exit 1
fi
