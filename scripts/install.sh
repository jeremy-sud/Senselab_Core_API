#!/bin/bash

# Script de Instalación Automatizada - Senselab Core API
# Senselab
# Para Ubuntu/Debian - Bash

set -euo pipefail  # Detener si hay errores, evitar uso de variables no inicializadas

echo "=================================================="
echo "  SENSELAB CORE API - Instalación Automatizada"
echo "  Senselab"
echo "=================================================="
echo ""

# Colores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Función para imprimir mensajes
print_success() {
    echo -e "${GREEN}✓ $1${NC}"
}

print_error() {
    echo -e "${RED}✗ $1${NC}"
}

print_info() {
    echo -e "${YELLOW}ℹ $1${NC}"
}

# Verificar si estamos en el directorio correcto
if [ ! -f "artisan" ]; then
    print_error "Este script debe ejecutarse desde el directorio raíz del proyecto (donde está artisan)"
    exit 1
fi

print_info "Iniciando instalación..."
echo ""

# 1. Verificar requisitos
echo "Paso 1: Verificando requisitos del sistema..."

# Verificar PHP
if ! command -v php &> /dev/null; then
    print_error "PHP no está instalado"
    exit 1
fi

PHP_VERSION=$(php -r 'echo PHP_VERSION;')
print_success "PHP $PHP_VERSION instalado"

# Verificar Composer
if ! command -v composer &> /dev/null; then
    print_error "Composer no está instalado"
    exit 1
fi

COMPOSER_VERSION=$(composer -V | cut -d ' ' -f 3)
print_success "Composer $COMPOSER_VERSION instalado"

# Verificar pnpm (opcional pero recomendado)
if command -v pnpm &> /dev/null; then
    PNPM_VERSION=$(pnpm --version)
    print_success "pnpm $PNPM_VERSION instalado"
else
    print_info "pnpm no instalado (opcional - solo para frontend)"
    print_info "Para instalar: npm install -g pnpm"
fi

# Verificar MySQL
if ! command -v mysql &> /dev/null; then
    print_error "MySQL no está instalado"
    exit 1
fi

MYSQL_VERSION=$(mysql --version | cut -d ' ' -f 6 | cut -d ',' -f 1)
print_success "MySQL $MYSQL_VERSION instalado"

echo ""

# 2. Instalar dependencias de Composer
echo "Paso 2: Instalando dependencias de PHP..."
composer install --no-interaction --prefer-dist --optimize-autoloader
print_success "Dependencias de Composer instaladas"
echo ""

# 3. Configurar .env
echo "Paso 3: Configurando archivo de entorno..."

if [ ! -f ".env" ]; then
    cp .env.example .env
    print_success "Archivo .env creado desde .env.example"
else
    print_info "Archivo .env ya existe, no se sobrescribirá"
fi

# Generar APP_KEY
php artisan key:generate --force
print_success "Clave de aplicación generada"
echo ""

# 4. Solicitar credenciales de base de datos
echo "Paso 4: Configuración de Base de Datos"
echo "----------------------------------------"

read -r -p "Usuario de MySQL [root]: " DB_USER
DB_USER=${DB_USER:-root}

read -r -s -p "Password de MySQL: " DB_PASS
echo ""

read -r -p "Nombre de la base de datos [api_db]: " DB_NAME
DB_NAME=${DB_NAME:-api_db}

read -r -p "Nombre de la base de datos de testing [api_db_testing]: " DB_TEST_NAME
DB_TEST_NAME=${DB_TEST_NAME:-api_db_testing}

read -r -p "Host de MySQL [127.0.0.1]: " DB_HOST
DB_HOST=${DB_HOST:-127.0.0.1}

read -r -p "Puerto de MySQL [3306]: " DB_PORT
DB_PORT=${DB_PORT:-3306}

# Función robusta para actualizar o añadir claves en .env (escapa / adecuadamente)
update_env() {
    local key="$1"
    local value="$2"
    if grep -qE "^${key}=" .env; then
        # usar # como delimitador para evitar problemas con /
        sed -i "s#^${key}=.*#${key}=${value}#" .env
    else
        printf "%s=%s\n" "${key}" "${value}" >> .env
    fi
}

# Actualizar .env de forma segura
update_env "DB_CONNECTION" "mysql"
update_env "DB_HOST" "$DB_HOST"
update_env "DB_PORT" "$DB_PORT"
update_env "DB_USERNAME" "$DB_USER"
update_env "DB_PASSWORD" "$DB_PASS"
update_env "DB_DATABASE" "$DB_NAME"
update_env "DB_DATABASE_TEST" "$DB_TEST_NAME"

print_success "Archivo .env actualizado con credenciales de BD"
echo ""

# 5. Crear bases de datos
echo "Paso 5: Creando bases de datos..."

# Crear un archivo temporal de configuración MySQL para evitar exponer la contraseña en la línea de comandos
MYSQL_CNF=$(mktemp)
trap 'rm -f "${MYSQL_CNF}"' EXIT
cat > "${MYSQL_CNF}" <<EOF
[client]
user=${DB_USER}
password=${DB_PASS}
host=${DB_HOST}
port=${DB_PORT}
EOF

# Crear base de datos principal usando --defaults-extra-file
if mysql --defaults-extra-file="${MYSQL_CNF}" -e "CREATE DATABASE IF NOT EXISTS \\`${DB_NAME}\\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" 2>/dev/null; then
    print_success "Base de datos '$DB_NAME' creada"
else
    print_error "Error al crear base de datos '$DB_NAME'"
    print_info "Verifica que las credenciales de MySQL sean correctas o que el servidor esté accesible"
    rm -f "${MYSQL_CNF}"
    exit 1
fi

# Crear base de datos de testing
if mysql --defaults-extra-file="${MYSQL_CNF}" -e "CREATE DATABASE IF NOT EXISTS \\`${DB_TEST_NAME}\\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" 2>/dev/null; then
    print_success "Base de datos '$DB_TEST_NAME' creada"
else
    print_error "Error al crear base de datos de testing '$DB_TEST_NAME'"
fi

# Remover archivo temporal (trap eliminará si el script sale prematuramente)
rm -f "${MYSQL_CNF}"

echo ""

# 6. Ejecutar migraciones
echo "Paso 6: Ejecutando migraciones..."
php artisan migrate --force
print_success "Migraciones ejecutadas (82 tablas creadas)"
echo ""

# 7. Ejecutar seeders
echo "Paso 7: Cargando datos maestros y de prueba..."
php artisan db:seed --force
print_success "Seeders ejecutados (112 registros cargados)"
echo ""

# Detalle de datos cargados
print_info "Datos cargados:"
echo "  • 2 Regímenes Tributarios"
echo "  • 6 Formas de Pago"
echo "  • 8 Tipos de Cuentas"
echo "  • 11 Unidades de Medida"
echo "  • 68 Permisos (17 módulos × 4 acciones)"
echo "  • 7 Roles (Administrador, Gerente, Contador, etc.)"
echo "  • 7 Cargos"
echo "  • 1 Empresa demo (Senselab)"
echo "  • 1 Usuario administrador"
echo ""

# 8. Crear enlace simbólico de storage
echo "Paso 8: Creando enlaces simbólicos..."
php artisan storage:link
print_success "Enlaces simbólicos creados"
echo ""

# 9. Limpiar cachés
echo "Paso 9: Limpiando cachés..."
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
print_success "Cachés limpiados"
echo ""

# 10. Ejecutar tests (opcional)
echo "Paso 10: Tests (opcional)"
read -r -p "¿Deseas ejecutar los tests ahora? (s/N): " RUN_TESTS
RUN_TESTS=${RUN_TESTS:-N}

if [[ "$RUN_TESTS" =~ ^[Ss]$ ]]; then
    echo "Ejecutando tests..."
    php artisan test
    echo ""
fi

# Resumen final
echo "=================================================="
echo -e "${GREEN}✓ INSTALACIÓN COMPLETADA EXITOSAMENTE${NC}"
echo "=================================================="
echo ""
echo "🚀 Servidor de desarrollo:"
echo "   php artisan serve"
echo ""
echo "🌐 URLs importantes:"
echo "   API:     http://localhost:8000/api"
echo "   Swagger: http://localhost:8000/api/documentation"
echo ""
echo "🔐 Credenciales de acceso:"
echo "   Email:    admin@senselab.com"
echo "   Password: admin123"
echo "   Permisos: 68 (todos los módulos)"
echo ""
echo "📊 Empresa demo creada:"
echo "   Nombre:  Senselab"
echo "   Cédula:  3-101-123456"
echo ""
echo "📚 Documentación:"
echo "   README.md              - Documentación principal"
echo "   INSTALLATION_GUIDE.md  - Guía de instalación detallada"
echo "   API_DOCUMENTATION.md   - Endpoints (420+ rutas)"
echo "   SECURITY.md            - Guía de seguridad OWASP Top 10"
echo "   TESTING_GUIDE.md       - Guía de testing"
echo ""
echo "🧪 Tests:"
echo "   php artisan test       - Ejecutar suite de tests (100+ tests)"
echo ""
print_success "¡Listo para desarrollar!"
echo ""
