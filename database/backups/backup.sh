#!/bin/bash

################################################################################
# Script de Backup Automático de Base de Datos
# Sistema Ursol CAST API
# 
# Este script realiza backups diarios de la base de datos MySQL y gestiona
# la rotación de backups antiguos.
################################################################################

set -e  # Salir si hay algún error

# Configuración
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(dirname "$(dirname "$SCRIPT_DIR")")"
BACKUP_DIR="${SCRIPT_DIR}"
DATE=$(date +"%Y%m%d_%H%M%S")
RETENTION_DAYS=7

# Colores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Función para logging
log() {
    echo -e "${GREEN}[$(date +'%Y-%m-%d %H:%M:%S')]${NC} $1"
}

error() {
    echo -e "${RED}[ERROR]${NC} $1" >&2
}

warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

# Cargar variables de entorno
if [ -f "${PROJECT_ROOT}/.env" ]; then
    export $(cat "${PROJECT_ROOT}/.env" | grep -v '^#' | grep -v '^\s*$' | xargs)
    log "Variables de entorno cargadas desde .env"
else
    error "Archivo .env no encontrado en ${PROJECT_ROOT}"
    exit 1
fi

# Validar variables requeridas
if [ -z "$DB_DATABASE" ] || [ -z "$DB_USERNAME" ] || [ -z "$DB_HOST" ]; then
    error "Variables de DB no configuradas correctamente en .env"
    exit 1
fi

# Crear directorio de backups si no existe
mkdir -p "$BACKUP_DIR"

# Nombre del archivo de backup
BACKUP_FILE="${BACKUP_DIR}/backup_${DB_DATABASE}_${DATE}.sql.gz"

log "Iniciando backup de base de datos: ${DB_DATABASE}"
log "Servidor: ${DB_HOST}:${DB_PORT:-3306}"

# Realizar backup usando mysqldump
# Nota: Si DB_PASSWORD está vacío, no se incluye la opción -p
if [ -z "$DB_PASSWORD" ]; then
    MYSQL_PWD="" mysqldump \
        -h "$DB_HOST" \
        -P "${DB_PORT:-3306}" \
        -u "$DB_USERNAME" \
        --single-transaction \
        --routines \
        --triggers \
        --events \
        --databases "$DB_DATABASE" \
        2>/dev/null | gzip > "$BACKUP_FILE"
else
    MYSQL_PWD="$DB_PASSWORD" mysqldump \
        -h "$DB_HOST" \
        -P "${DB_PORT:-3306}" \
        -u "$DB_USERNAME" \
        --single-transaction \
        --routines \
        --triggers \
        --events \
        --databases "$DB_DATABASE" \
        2>/dev/null | gzip > "$BACKUP_FILE"
fi

# Verificar que el backup se creó correctamente
if [ -f "$BACKUP_FILE" ]; then
    BACKUP_SIZE=$(du -h "$BACKUP_FILE" | cut -f1)
    log "✓ Backup completado exitosamente: $BACKUP_FILE"
    log "  Tamaño: $BACKUP_SIZE"
else
    error "Falló la creación del backup"
    exit 1
fi

# Rotación de backups antiguos
log "Limpiando backups antiguos (conservando últimos ${RETENTION_DAYS} días)..."

DELETED_COUNT=0
while IFS= read -r old_backup; do
    rm -f "$old_backup"
    DELETED_COUNT=$((DELETED_COUNT + 1))
    log "  Eliminado: $(basename "$old_backup")"
done < <(find "$BACKUP_DIR" -name "backup_*.sql.gz" -type f -mtime +$RETENTION_DAYS)

if [ $DELETED_COUNT -eq 0 ]; then
    log "  No hay backups antiguos para eliminar"
else
    log "  Eliminados $DELETED_COUNT backup(s) antiguo(s)"
fi

# Listar backups actuales
log "Backups disponibles:"
ls -lh "$BACKUP_DIR"/backup_*.sql.gz 2>/dev/null | awk '{print "  " $9 " - " $5}' || log "  (ninguno)"

# Estadísticas finales
BACKUP_COUNT=$(find "$BACKUP_DIR" -name "backup_*.sql.gz" -type f | wc -l)
TOTAL_SIZE=$(du -sh "$BACKUP_DIR" 2>/dev/null | cut -f1)

log "═══════════════════════════════════════════════════════════"
log "Resumen de Backups"
log "  Total de backups: $BACKUP_COUNT"
log "  Espacio utilizado: $TOTAL_SIZE"
log "  Retención: ${RETENTION_DAYS} días"
log "═══════════════════════════════════════════════════════════"

exit 0
