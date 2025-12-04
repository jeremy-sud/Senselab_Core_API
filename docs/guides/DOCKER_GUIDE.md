# Docker - Guía de Uso
# Ursol CAST API

Este documento explica cómo usar Docker para desarrollar y desplegar la Ursol CAST API.

## 📋 Tabla de Contenidos

1. [Requisitos Previos](#requisitos-previos)
2. [Instalación Rápida](#instalación-rápida)
3. [Servicios Disponibles](#servicios-disponibles)
4. [Comandos Útiles](#comandos-útiles)
5. [Desarrollo](#desarrollo)
6. [Producción](#producción)
7. [Troubleshooting](#troubleshooting)

---

## 🔧 Requisitos Previos

- **Docker**: 20.10 o superior
- **Docker Compose**: 2.0 o superior
- **Git**: Para clonar el repositorio
- **Make**: (Opcional) Para usar el Makefile

### Verificar Instalación

```bash
docker --version
docker-compose --version
```

---

## 🚀 Instalación Rápida

### Opción 1: Script Automático (Recomendado)

```bash
# Dar permisos de ejecución al script
chmod +x docker-start.sh

# Ejecutar instalación completa
./docker-start.sh
```

### Opción 2: Usando Makefile

```bash
# Ver todos los comandos disponibles
make help

# Instalación completa
make install
```

### Opción 3: Manual

```bash
# 1. Crear archivo .env
cp .env.docker .env

# 2. Construir contenedores
docker-compose build

# 3. Iniciar servicios
docker-compose up -d

# 4. Instalar dependencias
docker-compose exec php composer install

# 5. Generar APP_KEY
docker-compose exec php php artisan key:generate

# 6. Ejecutar migraciones
docker-compose exec php php artisan migrate

# 7. Ejecutar seeders
docker-compose exec php php artisan db:seed

# 8. Generar Swagger
docker-compose exec php php artisan l5-swagger:generate
```

---

## 🐳 Servicios Disponibles

### Servicios Principales

| Servicio | Puerto | Descripción |
|----------|--------|-------------|
| **nginx** | 8000 | Servidor web |
| **php** | 9000 | PHP-FPM 8.2 con Laravel |
| **mysql** | 3306 | MySQL 8.0 |
| **redis** | 6379 | Redis 7 (cache, sesiones, colas) |

### Servicios Opcionales (Profiles)

| Servicio | Puerto | Profile | Descripción |
|----------|--------|---------|-------------|
| **phpmyadmin** | 8080 | tools | Administrador de MySQL |
| **mailhog** | 8025 | development | Testing de emails |
| **queue** | - | production | Worker de colas |
| **scheduler** | - | production | Tareas programadas |

### Activar Servicios Opcionales

```bash
# Con PHPMyAdmin
docker-compose --profile tools up -d

# Entorno de desarrollo completo
docker-compose -f docker-compose.yml -f docker-compose.dev.yml up -d
# O usando make
make dev

# Producción con queue y scheduler
docker-compose --profile production up -d
# O usando make
make prod-up
```

---

## 🛠️ Comandos Útiles

### Gestión de Contenedores

```bash
# Ver estado de contenedores
make ps
# O
docker-compose ps

# Ver logs
make logs                # Todos los servicios
make logs-php           # Solo PHP
make logs-nginx         # Solo Nginx
make logs-mysql         # Solo MySQL

# Reiniciar servicios
make restart
# O
docker-compose restart

# Detener servicios
make down
# O
docker-compose down

# Eliminar contenedores y volúmenes
make clean
```

### Acceso a Shells

```bash
# Shell de PHP (Alpine Linux)
make shell
# O
docker-compose exec php sh

# MySQL CLI
make shell-mysql
# O
docker-compose exec mysql mysql -u ursol_user -pursol_password api_db

# Redis CLI
make shell-redis
# O
docker-compose exec redis redis-cli
```

### Artisan Commands

```bash
# Ejecutar cualquier comando artisan
make artisan CMD="route:list"
# O
docker-compose exec php php artisan route:list

# Migraciones
make migrate              # Ejecutar migraciones
make migrate-fresh       # Resetear BD y migrar
make seed                # Ejecutar seeders
make fresh               # Resetear, migrar y seedear

# Cache
make cache-clear         # Limpiar todos los cachés
make optimize            # Optimizar para producción

# Swagger
make swagger             # Regenerar documentación
```

### Composer

```bash
# Instalar dependencias
make composer-install
# O
docker-compose exec php composer install

# Actualizar dependencias
make composer-update

# Dump autoload
make composer-dump
```

### Testing

```bash
# Ejecutar todos los tests
make test
# O
docker-compose exec php php artisan test

# Tests específicos
make test-filter FILTER="AuthTest"

# Con cobertura
make test-coverage
```

---

## 💻 Desarrollo

### Entorno de Desarrollo Completo

```bash
# Iniciar con PHPMyAdmin y Mailhog
make dev

# URLs disponibles:
# - API: http://localhost:8000
# - Swagger: http://localhost:8000/api/documentation
# - PHPMyAdmin: http://localhost:8080
# - Mailhog: http://localhost:8025
```

### Configuración de .env para Desarrollo

El archivo `.env.docker` ya incluye configuración optimizada para desarrollo:

```env
APP_ENV=local
APP_DEBUG=true

# Mailhog para testing de emails
MAIL_HOST=mailhog
MAIL_PORT=1025
```

### Hot Reload / Desarrollo en Tiempo Real

Los volúmenes están configurados con `:delegated` para mejor rendimiento:

```yaml
volumes:
  - ./:/var/www/html:delegated
```

Los cambios en el código se reflejan inmediatamente (sin rebuild).

### Debugging

Para habilitar Xdebug (opcional):

```bash
# Descomentar en docker-compose.dev.yml:
environment:
  - XDEBUG_MODE=develop,debug
  - XDEBUG_CONFIG=client_host=host.docker.internal
```

---

## 🚀 Producción

### Iniciar Modo Producción

```bash
# Con queue worker y scheduler
make prod-up
# O
docker-compose --profile production up -d
```

### Optimizaciones

```bash
# Optimizar aplicación
make optimize

# Esto ejecuta:
# - config:cache
# - route:cache
# - view:cache
# - composer dump-autoload -o
```

### Variables de Entorno Producción

Modificar `.env`:

```env
APP_ENV=production
APP_DEBUG=false

# Configurar base de datos real
DB_HOST=tu-servidor-mysql
DB_DATABASE=api_db_production
DB_USERNAME=usuario_produccion
DB_PASSWORD=contraseña_segura

# Redis en producción
REDIS_HOST=tu-servidor-redis

# Cache y sesiones
CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis
```

### Build para Producción

```bash
# Construir con optimizaciones
docker-compose build --no-cache

# Multi-stage build ya optimizado en Dockerfile:
# - Dependencias sin dev
# - Autoloader optimizado y authoritative
# - Extensiones PHP solo necesarias
```

### Backup de Base de Datos

```bash
# Crear backup
make backup-db

# Manual
docker-compose exec mysql mysqldump -u ursol_user -pursol_password api_db > backup.sql

# Restaurar backup
docker-compose exec -T mysql mysql -u ursol_user -pursol_password api_db < backup.sql
```

---

## 🔍 Troubleshooting

### Error: "Cannot connect to Docker daemon"

```bash
# Linux/Mac: Iniciar Docker
sudo systemctl start docker

# Windows: Iniciar Docker Desktop
```

### Error: "Port already in use"

```bash
# Ver qué usa el puerto 8000
sudo lsof -i :8000

# Cambiar puertos en .env
NGINX_PORT=8001
```

### MySQL no inicia / errores de conexión

```bash
# Ver logs de MySQL
make logs-mysql

# Verificar health check
docker-compose ps

# Resetear contenedor MySQL
docker-compose down
docker volume rm ursol-cast-api_mysql_data
docker-compose up -d
```

### Permisos de archivos (Linux)

```bash
# Ajustar permisos desde el contenedor
docker-compose exec php chmod -R 775 storage bootstrap/cache
docker-compose exec php chown -R laravel:laravel storage bootstrap/cache
```

### Limpiar todo y empezar de cero

```bash
# Detener y eliminar todo
make clean

# O manual
docker-compose down -v
docker system prune -a --volumes

# Reinstalar
make install
```

### Composer muy lento

```bash
# Ejecutar con opciones de caché
docker-compose exec php composer install --prefer-dist --no-progress
```

### Redis no conecta

```bash
# Verificar que Redis está corriendo
docker-compose ps redis

# Test de conexión
docker-compose exec redis redis-cli ping
# Debe responder: PONG

# Ver logs
make logs-redis
```

---

## 📊 Estructura de Archivos Docker

```
.
├── Dockerfile                      # Imagen principal PHP-FPM
├── docker-compose.yml              # Configuración base
├── docker-compose.dev.yml          # Configuración desarrollo
├── .dockerignore                   # Archivos excluidos
├── .env.docker                     # Variables de entorno
├── docker-start.sh                 # Script instalación rápida
├── Makefile                        # Comandos útiles
└── docker/
    ├── nginx/
    │   ├── default.conf           # Virtual host Laravel
    │   └── nginx.conf             # Configuración Nginx
    ├── php/
    │   ├── php.ini                # Configuración PHP
    │   └── php-fpm.conf           # Configuración PHP-FPM
    ├── mysql/
    │   ├── my.cnf                 # Configuración MySQL
    │   └── init.sql               # Script inicialización BD
    └── redis/
        └── redis.conf             # Configuración Redis
```

---

## 🔗 URLs Importantes

| Servicio | URL | Credenciales |
|----------|-----|--------------|
| API | http://localhost:8000 | - |
| Swagger | http://localhost:8000/api/documentation | - |
| PHPMyAdmin | http://localhost:8080 | ursol_user / ursol_password |
| Mailhog | http://localhost:8025 | - |
| Admin API | - | admin@ursol.com / admin123 |

---

## 📞 Soporte

**Sistemas Ursol S.A.**  
Email: sistemas@ursol.com

Para más información, consulta:
- `README.md` - Documentación general del proyecto
- `TESTING_GUIDE.md` - Guía de testing
- `API_DOCUMENTATION.md` - Documentación de la API
