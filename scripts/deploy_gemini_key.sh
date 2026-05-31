#!/bin/bash

#############################################
# Script de Configuración de GEMINI_API_KEY
# Senselab Core API — IA en Producción
# Uso: bash scripts/deploy_gemini_key.sh <SERVER_IP> [SSH_USER]
# Ejemplo: bash scripts/deploy_gemini_key.sh 54.123.45.67 ubuntu
#############################################

set -e

# ── Parámetros ──────────────────────────────────────────────────────────────
SERVER_IP="${1}"
SSH_USER="${2:-ubuntu}"
PEM_KEY="$(dirname "$0")/../aws-pem/Senselab-key-holder.pem"
REMOTE_PATH="/home/ec2-user/Workspace/Senselab_Core_API"

# ── Leer la API key del .env local ──────────────────────────────────────────
SCRIPT_DIR="$(dirname "$0")"
LOCAL_ENV="${SCRIPT_DIR}/../.env"

GEMINI_KEY=""
if [[ -f "$LOCAL_ENV" ]]; then
    GEMINI_KEY=$(grep -E '^GEMINI_API_KEY=' "$LOCAL_ENV" | cut -d= -f2- | tr -d '"' | tr -d "'")
fi

# Colores
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m'

# ── Validaciones ─────────────────────────────────────────────────────────────
if [[ -z "$SERVER_IP" ]]; then
  echo -e "${RED}ERROR: Debes proporcionar la IP del servidor.${NC}"
  echo -e "Uso: bash scripts/deploy_gemini_key.sh <SERVER_IP> [SSH_USER]"
  exit 1
fi

if [[ -z "$GEMINI_KEY" ]]; then
  echo -e "${RED}ERROR: No se encontró GEMINI_API_KEY en el archivo .env local.${NC}"
  echo -e "Asegúrate de que el archivo .env tenga: GEMINI_API_KEY=tu_key"
  exit 1
fi

if [[ ! -f "$PEM_KEY" ]]; then
  echo -e "${RED}ERROR: No se encontró el archivo PEM en: ${PEM_KEY}${NC}"
  exit 1
fi

chmod 600 "$PEM_KEY"

SSH_CMD="ssh -i ${PEM_KEY} -o StrictHostKeyChecking=no ${SSH_USER}@${SERVER_IP}"

echo -e "${GREEN}============================================${NC}"
echo -e "${GREEN}  Senselab AI — Configurar GEMINI_API_KEY${NC}"
echo -e "${GREEN}  Servidor: ${SSH_USER}@${SERVER_IP}${NC}"
echo -e "${GREEN}============================================${NC}\n"

# ── Paso 1: Verificar conectividad SSH ───────────────────────────────────────
echo -e "${YELLOW}[1/4] Verificando conexión SSH al servidor...${NC}"
if ! $SSH_CMD "echo 'SSH OK'" > /dev/null 2>&1; then
  echo -e "${RED}ERROR: No se pudo conectar al servidor. Verifica IP y PEM key.${NC}"
  exit 1
fi
echo -e "${GREEN}✔ Conexión SSH establecida.${NC}\n"

# ── Paso 2: Configurar GEMINI_API_KEY en .env remoto ────────────────────────
echo -e "${YELLOW}[2/4] Configurando GEMINI_API_KEY en el servidor...${NC}"

$SSH_CMD bash << REMOTE_COMMANDS
  ENV_FILE="${REMOTE_PATH}/.env"

  if [[ ! -f "\$ENV_FILE" ]]; then
    echo "ERROR: No se encontró el archivo .env en \$ENV_FILE"
    exit 1
  fi

  # Verificar si la variable ya existe
  if grep -q '^GEMINI_API_KEY=' "\$ENV_FILE"; then
    echo "La variable GEMINI_API_KEY ya existe. Actualizando..."
    # Actualizar valor existente usando sed
    sudo sed -i "s|^GEMINI_API_KEY=.*|GEMINI_API_KEY=${GEMINI_KEY}|" "\$ENV_FILE"
    echo "✔ GEMINI_API_KEY actualizada correctamente."
  else
    echo "La variable GEMINI_API_KEY no existe. Añadiendo..."
    echo "" | sudo tee -a "\$ENV_FILE" > /dev/null
    echo "# Servicios de Inteligencia Artificial (Google Gemini)" | sudo tee -a "\$ENV_FILE" > /dev/null
    echo "GEMINI_API_KEY=${GEMINI_KEY}" | sudo tee -a "\$ENV_FILE" > /dev/null
    echo "✔ GEMINI_API_KEY añadida al archivo .env."
  fi

  # Mostrar el valor enmascarado para confirmar
  KEY_PREVIEW=\$(grep '^GEMINI_API_KEY=' "\$ENV_FILE" | cut -d= -f2- | cut -c1-10)
  echo "Valor configurado (primeros 10 caracteres): \${KEY_PREVIEW}..."
REMOTE_COMMANDS

echo -e "${GREEN}✔ Variable de entorno configurada.${NC}\n"

# ── Paso 3: Limpiar config cache y regenerar ────────────────────────────────
echo -e "${YELLOW}[3/4] Recargando configuración de Laravel...${NC}"

$SSH_CMD bash << REMOTE_COMMANDS
  cd "${REMOTE_PATH}"

  # Detectar si corre en Docker o directo
  if docker ps --format '{{.Names}}' 2>/dev/null | grep -qE 'senselab|app|php'; then
    APP_CONTAINER=\$(docker ps --format '{{.Names}}' | grep -E 'senselab.*app|senselab.*php|_php_|_app_' | head -1)

    # Fallback si el grep anterior no encuentra nada
    if [[ -z "\$APP_CONTAINER" ]]; then
      APP_CONTAINER=\$(docker ps --format '{{.Names}}' | grep -iE 'php|app' | head -1)
    fi

    if [[ -z "\$APP_CONTAINER" ]]; then
      echo "ERROR: No se encontró ningún contenedor PHP/App activo."
      docker ps --format '{{.Names}}'
      exit 1
    fi

    echo "Usando contenedor Docker: \$APP_CONTAINER"

    # Limpiar config cache para que lea el nuevo .env
    docker exec \$APP_CONTAINER php artisan config:clear
    docker exec \$APP_CONTAINER php artisan cache:clear

    # Regenerar config cache con la nueva key
    docker exec \$APP_CONTAINER php artisan config:cache

    # Verificar que Gemini está configurado
    IS_CONFIGURED=\$(docker exec \$APP_CONTAINER php artisan tinker --execute="echo app(App\Services\AI\GeminiService::class)->isConfigured() ? 'SI' : 'NO';" 2>/dev/null || echo "ERROR")

    echo "¿Gemini configurado en el contenedor? \$IS_CONFIGURED"
  else
    echo "Ejecutando artisan directamente (sin Docker)..."
    sudo php artisan config:clear
    sudo php artisan cache:clear
    sudo php artisan config:cache
    IS_CONFIGURED=\$(sudo php artisan tinker --execute="echo app(App\Services\AI\GeminiService::class)->isConfigured() ? 'SI' : 'NO';" 2>/dev/null || echo "ERROR")
    echo "¿Gemini configurado? \$IS_CONFIGURED"
  fi
REMOTE_COMMANDS

echo -e "${GREEN}✔ Configuración de Laravel recargada.${NC}\n"

# ── Paso 4: Verificar que la IA responde desde producción ───────────────────
echo -e "${YELLOW}[4/4] Verificando endpoint de IA en producción...${NC}"
sleep 2

AI_STATUS_URL="https://api.scisenselab.com/api/v1/ai/status"
HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" "$AI_STATUS_URL" 2>/dev/null || echo "000")

echo ""
echo -e "${GREEN}============================================${NC}"
echo -e "${GREEN}  Resultado de la Configuración${NC}"
echo -e "${GREEN}============================================${NC}"

if [[ "$HTTP_CODE" == "200" ]]; then
  echo -e "${GREEN}✔ Endpoint de IA responde correctamente (HTTP ${HTTP_CODE})${NC}"
  # Mostrar el body de la respuesta
  RESPONSE=$(curl -s "$AI_STATUS_URL" 2>/dev/null || echo "{}")
  echo -e "Respuesta: $RESPONSE"
elif [[ "$HTTP_CODE" == "401" || "$HTTP_CODE" == "403" ]]; then
  echo -e "${YELLOW}⚠ Endpoint requiere autenticación (HTTP ${HTTP_CODE}) — Esto es normal.${NC}"
  echo -e "${GREEN}✔ El servidor de IA está operativo.${NC}"
else
  echo -e "${YELLOW}⚠ El endpoint respondió con HTTP ${HTTP_CODE}.${NC}"
  echo -e "Verifica manualmente: curl $AI_STATUS_URL"
fi

echo ""
echo -e "${GREEN}✔ GEMINI_API_KEY configurada exitosamente en producción.${NC}"
echo ""
echo -e "${YELLOW}PRÓXIMOS PASOS si la IA aún no funciona:${NC}"
echo -e "  1. Verifica el log de Laravel: docker-compose logs php | grep -i gemini"
echo -e "  2. Prueba el endpoint directamente desde el servidor:"
echo -e "     curl https://api.scisenselab.com/api/v1/ai/status"
echo -e "  3. Asegúrate de que el contenedor tiene salida a internet (para llamar a api.google.com)"
echo ""
