#!/usr/bin/env bash
# ==============================================================================
# Senselab Core API - PM2 Auto-Healer Daemon & Health Sentinel
# FASE 25.3 (v5.3.0)
#
# Monitorea activamente la salud del backend en http://localhost:8000/api/health/live.
# En caso de no respuesta (5xx, timeout o caída de proceso), dispara
# un reinicio graceful a través de PM2 y registra la incidencia en bitácora.
#
# Uso:
#   ./scripts/pm2_auto_healer.sh          # Ejecución única (para cron cada minuto)
#   ./scripts/pm2_auto_healer.sh --daemon # Ejecución continua como demonio en background
# ==============================================================================

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
API_DIR="$(cd "${SCRIPT_DIR}/.." && pwd)"
LOG_FILE="${API_DIR}/storage/logs/auto_healer.log"
HEALTH_URL="http://localhost:8000/api/health/live"
PM2_PROCESS="senselab-api"
CHECK_INTERVAL_SEC=15
TIMEOUT_SEC=5

mkdir -p "$(dirname "${LOG_FILE}")"

log() {
    local level="$1"
    local message="$2"
    local timestamp
    timestamp="$(date '+%Y-%m-%d %H:%M:%S')"
    echo "[${timestamp}] [${level}] ${message}" | tee -a "${LOG_FILE}"
}

check_and_heal() {
    local response
    local http_code

    http_code=$(curl -s -o /dev/null -w "%{http_code}" --max-time "${TIMEOUT_SEC}" "${HEALTH_URL}" 2>/dev/null || echo "000")

    if [ "${http_code}" = "200" ]; then
        return 0
    fi

    log "WARNING" "Fallo detectado en ${HEALTH_URL}. Código HTTP: ${http_code}. Iniciando auto-curación de ${PM2_PROCESS}..."

    # Intentar reiniciar proceso PM2
    if command -v pm2 >/dev/null 2>&1; then
        if pm2 restart "${PM2_PROCESS}" >> "${LOG_FILE}" 2>&1; then
            log "INFO" "Proceso ${PM2_PROCESS} reiniciado exitosamente por PM2."
            sleep 3
            # Validar si recuperó
            local retry_code
            retry_code=$(curl -s -o /dev/null -w "%{http_code}" --max-time "${TIMEOUT_SEC}" "${HEALTH_URL}" 2>/dev/null || echo "000")
            if [ "${retry_code}" = "200" ]; then
                log "INFO" "Servicio restaurado con éxito (HTTP 200)."
            else
                log "ERROR" "Servicio no respondió con 200 tras reinicio (HTTP: ${retry_code}). Requiere revisión de contenedor o logs."
            fi
        else
            log "ERROR" "Fallo al ejecutar pm2 restart ${PM2_PROCESS}."
        fi
    else
        log "ERROR" "Comando pm2 no encontrado en PATH."
    fi
}

if [ "${1:-}" = "--daemon" ]; then
    log "INFO" "Sentinel Auto-Healer iniciado en modo demonio (intervalo: ${CHECK_INTERVAL_SEC}s)."
    while true; do
        check_and_heal
        sleep "${CHECK_INTERVAL_SEC}"
    done
else
    check_and_heal
fi
