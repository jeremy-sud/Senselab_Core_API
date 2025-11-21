#!/bin/bash

# Script de Instalación Automatizada - Ursol CAST API
# Sistemas Ursol S.A.
# Para Ubuntu/Debian - Bash

set -e  # Detener si hay errores

echo "=================================================="
echo "  URSOL CAST API - Instalación Automatizada"
echo "  Sistemas Ursol S.A."
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

read -p "Usuario de MySQL [root]: " DB_USER
DB_USER=${DB_USER:-root}

read -sp "Password de MySQL: " DB_PASS
echo ""

read -p "Nombre de la base de datos [api_db]: " DB_NAME
DB_NAME=${DB_NAME:-api_db}

read -p "Nombre de la base de datos de testing [api_db_testing]: " DB_TEST_NAME
DB_TEST_NAME=${DB_TEST_NAME:-api_db_testing}

# Actualizar .env
sed -i "s/DB_USERNAME=.*/DB_USERNAME=$DB_USER/" .env
sed -i "s/DB_PASSWORD=.*/DB_PASSWORD=$DB_PASS/" .env
sed -i "s/DB_DATABASE=.*/DB_DATABASE=$DB_NAME/" .env

print_success "Archivo .env actualizado con credenciales de BD"
echo ""

# 5. Crear bases de datos
echo "Paso 5: Creando bases de datos..."

# Crear base de datos principal
mysql -u "$DB_USER" -p"$DB_PASS" -e "CREATE DATABASE IF NOT EXISTS $DB_NAME CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" 2>/dev/null
if [ $? -eq 0 ]; then
    print_success "Base de datos '$DB_NAME' creada"
else
    print_error "Error al crear base de datos '$DB_NAME'"
    print_info "Verifica que las credenciales de MySQL sean correctas"
    exit 1
fi

# Crear base de datos de testing
mysql -u "$DB_USER" -p"$DB_PASS" -e "CREATE DATABASE IF NOT EXISTS $DB_TEST_NAME CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" 2>/dev/null
if [ $? -eq 0 ]; then
    print_success "Base de datos '$DB_TEST_NAME' creada"
else
    print_error "Error al crear base de datos de testing '$DB_TEST_NAME'"
fi

echo ""

# 6. Ejecutar migraciones
echo "Paso 6: Ejecutando migraciones..."
php artisan migrate --force
print_success "Migraciones ejecutadas (66 tablas creadas)"
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
echo "  • 1 Empresa demo (Sistemas Ursol S.A.)"
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
read -p "¿Deseas ejecutar los tests ahora? (s/N): " RUN_TESTS
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
echo "   Email:    admin@ursol.com"
echo "   Password: admin123"
echo "   Permisos: 68 (todos los módulos)"
echo ""
echo "📊 Empresa demo creada:"
echo "   Nombre:  Sistemas Ursol S.A."
echo "   Cédula:  3-101-123456"
echo ""
echo "📚 Documentación:"
echo "   README.md              - Documentación principal"
echo "   INSTALLATION_GUIDE.md  - Guía de instalación detallada"
echo "   API_DOCUMENTATION.md   - Endpoints (413 rutas)"
echo "   TESTING_GUIDE.md       - Guía de testing"
echo ""
echo "🧪 Tests:"
echo "   php artisan test       - Ejecutar suite de tests (81 tests)"
echo ""
print_success "¡Listo para desarrollar!"
echo ""
