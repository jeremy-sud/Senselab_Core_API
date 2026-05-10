#!/bin/bash

# Script de inicio rápido para Docker
# Senselab Core API

set -e

echo "🚀 Iniciando Senselab Core API con Docker..."
echo ""

# Verificar si Docker está instalado
if ! command -v docker &> /dev/null; then
    echo "❌ Error: Docker no está instalado"
    echo "Por favor, instala Docker desde: https://docs.docker.com/get-docker/"
    exit 1
fi

# Verificar si Docker Compose está instalado
if ! command -v docker-compose &> /dev/null; then
    echo "❌ Error: Docker Compose no está instalado"
    echo "Por favor, instala Docker Compose desde: https://docs.docker.com/compose/install/"
    exit 1
fi

# Verificar si Docker está corriendo
if ! docker info &> /dev/null; then
    echo "❌ Error: Docker no está corriendo"
    echo "Por favor, inicia Docker Desktop o el servicio de Docker"
    exit 1
fi

echo "✓ Docker está instalado y corriendo"
echo ""

# Crear archivo .env si no existe
if [ ! -f .env ]; then
    echo "📝 Creando archivo .env..."
    cp .env.docker .env
    echo "✓ Archivo .env creado desde .env.docker"
    echo ""
fi

# Construir contenedores
echo "🔨 Construyendo contenedores Docker..."
docker-compose build
echo "✓ Contenedores construidos"
echo ""

# Iniciar contenedores
echo "🚢 Iniciando contenedores..."
docker-compose up -d
echo "✓ Contenedores iniciados"
echo ""

# Esperar a que MySQL esté listo
echo "⏳ Esperando a que MySQL esté listo..."
sleep 15
echo "✓ MySQL está listo"
echo ""

# Instalar dependencias de Composer
echo "📦 Instalando dependencias de Composer..."
docker-compose exec -T php composer install --no-interaction --prefer-dist
echo "✓ Dependencias instaladas"
echo ""

# Generar APP_KEY
echo "🔑 Generando APP_KEY..."
docker-compose exec -T php php artisan key:generate --no-interaction
echo "✓ APP_KEY generada"
echo ""

# Ejecutar migraciones
echo "🗄️  Ejecutando migraciones..."
docker-compose exec -T php php artisan migrate --no-interaction
echo "✓ Migraciones ejecutadas"
echo ""

# Ejecutar seeders
echo "🌱 Ejecutando seeders..."
docker-compose exec -T php php artisan db:seed --no-interaction
echo "✓ Seeders ejecutados"
echo ""

# Generar documentación Swagger
echo "📚 Generando documentación Swagger..."
docker-compose exec -T php php artisan l5-swagger:generate
echo "✓ Documentación generada"
echo ""

# Configurar permisos
echo "🔐 Configurando permisos..."
docker-compose exec -T php chmod -R 775 storage bootstrap/cache
echo "✓ Permisos configurados"
echo ""

echo "✅ ¡Instalación completada!"
echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "🌐 URLs disponibles:"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""
echo "  API:        http://localhost:8000"
echo "  Swagger:    http://localhost:8000/api/documentation"
echo "  PHPMyAdmin: http://localhost:8080 (solo con profile 'tools')"
echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "🔑 Credenciales por defecto:"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""
echo "  Email:    admin@senselab.com"
echo "  Password: admin123"
echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "📖 Comandos útiles:"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""
echo "  make help          - Ver todos los comandos disponibles"
echo "  make logs          - Ver logs de todos los contenedores"
echo "  make shell         - Acceder a shell de PHP"
echo "  make test          - Ejecutar tests"
echo "  make down          - Detener contenedores"
echo "  make restart       - Reiniciar contenedores"
echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
