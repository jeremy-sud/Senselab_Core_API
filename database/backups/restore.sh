#!/bin/bash

################################################################################
# Script de Restauración de Base de Datos
# Sistema Senselab Core API
# 
# Este script restaura un backup de la base de datos MySQL.
################################################################################

set -e  # Salir si hay algún error

# Configuración
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(dirname "$(dirname "$SCRIPT_DIR")")"
BACKUP_DIR="${SCRIPT_DIR}"

# Colores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
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

info() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

# Función para mostrar uso
usage() {
    echo "Uso: $0 [ARCHIVO_BACKUP]"
    echo ""
    echo "Restaura un backup de la base de datos."
    echo ""
    echo "Opciones:"
    echo "  ARCHIVO_BACKUP  Ruta al archivo de backup (.sql.gz)"
    echo "                  Si no se especifica, se muestra lista para seleccionar"
    echo ""
    echo "Ejemplos:"
    echo "  $0                                    # Seleccionar de lista"
    echo "  $0 backup_api_db_20240115_120000.sql.gz  # Restaurar archivo específico"
    exit 1
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

# Función para listar backups disponibles
list_backups() {
    local backups=($(find "$BACKUP_DIR" -name "backup_*.sql.gz" -type f | sort -r))
    
    if [ ${#backups[@]} -eq 0 ]; then
        error "No hay backups disponibles en $BACKUP_DIR"
        exit 1
    fi
    
    echo ""
    info "Backups disponibles:"
    echo ""
    
    local i=1
    for backup in "${backups[@]}"; do
        local filename=$(basename "$backup")
        local size=$(du -h "$backup" | cut -f1)
        local date=$(stat -c %y "$backup" | cut -d' ' -f1,2 | cut -d'.' -f1)
        printf "  ${BLUE}%2d)${NC} %-45s ${GREEN}%8s${NC}  %s\n" "$i" "$filename" "$size" "$date"
        i=$((i + 1))
    done
    
    echo ""
}

# Función para seleccionar backup interactivamente
select_backup() {
    local backups=($(find "$BACKUP_DIR" -name "backup_*.sql.gz" -type f | sort -r))
    
    if [ ${#backups[@]} -eq 0 ]; then
        error "No hay backups disponibles"
        exit 1
    fi
    
    list_backups
    
    while true; do
        read -p "Seleccione el número de backup a restaurar (1-${#backups[@]}) o 'q' para salir: " selection
        
        if [ "$selection" = "q" ] || [ "$selection" = "Q" ]; then
            log "Operación cancelada"
            exit 0
        fi
        
        if [[ "$selection" =~ ^[0-9]+$ ]] && [ "$selection" -ge 1 ] && [ "$selection" -le ${#backups[@]} ]; then
            BACKUP_FILE="${backups[$((selection - 1))]}"
            break
        else
            warning "Selección inválida. Por favor ingrese un número entre 1 y ${#backups[@]}"
        fi
    done
}

# Determinar archivo de backup
if [ $# -eq 0 ]; then
    # No se proporcionó archivo, mostrar lista para seleccionar
    select_backup
elif [ $# -eq 1 ]; then
    BACKUP_FILE="$1"
    
    # Si es solo el nombre del archivo, agregar el directorio
    if [ ! -f "$BACKUP_FILE" ]; then
        BACKUP_FILE="${BACKUP_DIR}/$1"
    fi
    
    # Verificar que el archivo existe
    if [ ! -f "$BACKUP_FILE" ]; then
        error "Archivo de backup no encontrado: $BACKUP_FILE"
        echo ""
        list_backups
        exit 1
    fi
else
    usage
fi

# Mostrar información del backup
BACKUP_SIZE=$(du -h "$BACKUP_FILE" | cut -f1)
BACKUP_DATE=$(stat -c %y "$BACKUP_FILE" | cut -d'.' -f1)

echo ""
warning "═══════════════════════════════════════════════════════════"
warning "ADVERTENCIA: Esta operación REEMPLAZARÁ los datos actuales"
warning "═══════════════════════════════════════════════════════════"
echo ""
info "Información del backup:"
info "  Archivo: $(basename "$BACKUP_FILE")"
info "  Tamaño: $BACKUP_SIZE"
info "  Fecha: $BACKUP_DATE"
echo ""
info "Base de datos destino:"
info "  Servidor: ${DB_HOST}:${DB_PORT:-3306}"
info "  Base de datos: $DB_DATABASE"
echo ""

# Confirmación
read -p "¿Está seguro de que desea continuar? (escriba 'si' para confirmar): " confirmation

if [ "$confirmation" != "si" ] && [ "$confirmation" != "SI" ]; then
    log "Operación cancelada por el usuario"
    exit 0
fi

echo ""
log "Iniciando restauración..."

# Crear backup de seguridad antes de restaurar
SAFETY_BACKUP="${BACKUP_DIR}/pre_restore_backup_$(date +"%Y%m%d_%H%M%S").sql.gz"
log "Creando backup de seguridad en: $(basename "$SAFETY_BACKUP")"

if [ -z "$DB_PASSWORD" ]; then
    MYSQL_PWD="" mysqldump \
        -h "$DB_HOST" \
        -P "${DB_PORT:-3306}" \
        -u "$DB_USERNAME" \
        --single-transaction \
        --databases "$DB_DATABASE" \
        2>/dev/null | gzip > "$SAFETY_BACKUP"
else
    MYSQL_PWD="$DB_PASSWORD" mysqldump \
        -h "$DB_HOST" \
        -P "${DB_PORT:-3306}" \
        -u "$DB_USERNAME" \
        --single-transaction \
        --databases "$DB_DATABASE" \
        2>/dev/null | gzip > "$SAFETY_BACKUP"
fi

log "✓ Backup de seguridad creado"

# Restaurar el backup
log "Restaurando backup..."

if [ -z "$DB_PASSWORD" ]; then
    gunzip < "$BACKUP_FILE" | MYSQL_PWD="" mysql \
        -h "$DB_HOST" \
        -P "${DB_PORT:-3306}" \
        -u "$DB_USERNAME" \
        2>/dev/null
else
    gunzip < "$BACKUP_FILE" | MYSQL_PWD="$DB_PASSWORD" mysql \
        -h "$DB_HOST" \
        -P "${DB_PORT:-3306}" \
        -u "$DB_USERNAME" \
        2>/dev/null
fi

log "✓ Restauración completada exitosamente"

# Verificar la restauración
log "Verificando restauración..."

if [ -z "$DB_PASSWORD" ]; then
    TABLE_COUNT=$(MYSQL_PWD="" mysql \
        -h "$DB_HOST" \
        -P "${DB_PORT:-3306}" \
        -u "$DB_USERNAME" \
        -D "$DB_DATABASE" \
        -sse "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = '$DB_DATABASE'")
else
    TABLE_COUNT=$(MYSQL_PWD="$DB_PASSWORD" mysql \
        -h "$DB_HOST" \
        -P "${DB_PORT:-3306}" \
        -u "$DB_USERNAME" \
        -D "$DB_DATABASE" \
        -sse "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = '$DB_DATABASE'")
fi

echo ""
log "═══════════════════════════════════════════════════════════"
log "Resumen de Restauración"
log "  Base de datos: $DB_DATABASE"
log "  Tablas restauradas: $TABLE_COUNT"
log "  Backup de seguridad: $(basename "$SAFETY_BACKUP")"
log "═══════════════════════════════════════════════════════════"
echo ""
info "La restauración se completó exitosamente."
info "Si algo salió mal, puede restaurar el backup de seguridad:"
info "  ./restore.sh $(basename "$SAFETY_BACKUP")"
echo ""

exit 0
