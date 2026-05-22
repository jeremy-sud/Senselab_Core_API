#!/bin/bash

#############################################
# Script de Despliegue OAuth en Producción
# Senselab Core API — Google & Apple SSO
# Uso: bash scripts/deploy_oauth.sh <SERVER_IP> <SSH_USER>
# Ejemplo: bash scripts/deploy_oauth.sh 54.123.45.67 ubuntu
#############################################

set -e

# ── Parámetros ──────────────────────────────────────────────────────────────
SERVER_IP="${1}"
SSH_USER="${2:-ubuntu}"
PEM_KEY="$(dirname "$0")/../aws-pem/Senselab-key-holder.pem"
REMOTE_PATH="/var/www/html"
LOCAL_PATH="$(dirname "$0")/.."

# Colores
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m'

if [[ -z "$SERVER_IP" ]]; then
  echo -e "${RED}ERROR: Debes proporcionar la IP del servidor.${NC}"
  echo -e "Uso: bash scripts/deploy_oauth.sh <SERVER_IP> [SSH_USER]"
  exit 1
fi

SSH_CMD="ssh -i ${PEM_KEY} -o StrictHostKeyChecking=no ${SSH_USER}@${SERVER_IP}"
SCP_CMD="scp -i ${PEM_KEY} -o StrictHostKeyChecking=no"

echo -e "${GREEN}============================================${NC}"
echo -e "${GREEN}  Senselab OAuth Deployment — Producción${NC}"
echo -e "${GREEN}  Servidor: ${SSH_USER}@${SERVER_IP}${NC}"
echo -e "${GREEN}============================================${NC}\n"

# ── Paso 1: Subir los archivos nuevos al servidor ──────────────────────────
echo -e "${YELLOW}[1/5] Copiando archivos nuevos al servidor...${NC}"

$SCP_CMD \
  "${LOCAL_PATH}/app/Http/Controllers/API/GoogleAuthController.php" \
  "${LOCAL_PATH}/app/Http/Controllers/API/AppleAuthController.php" \
  "${SSH_USER}@${SERVER_IP}:/tmp/"

$SCP_CMD \
  "${LOCAL_PATH}/routes/api/auth.php" \
  "${LOCAL_PATH}/routes/api.php" \
  "${LOCAL_PATH}/config/services.php" \
  "${LOCAL_PATH}/composer.json" \
  "${LOCAL_PATH}/composer.lock" \
  "${SSH_USER}@${SERVER_IP}:/tmp/"

echo -e "${GREEN}✔ Archivos copiados.${NC}\n"

# ── Paso 2: Mover archivos al directorio correcto dentro del contenedor ─────
echo -e "${YELLOW}[2/5] Instalando archivos en el servidor...${NC}"

$SSH_CMD bash << REMOTE_COMMANDS
  # Mover controladores
  sudo cp /tmp/GoogleAuthController.php ${REMOTE_PATH}/app/Http/Controllers/API/
  sudo cp /tmp/AppleAuthController.php ${REMOTE_PATH}/app/Http/Controllers/API/
  
  # Mover rutas y configuración
  sudo cp /tmp/auth.php ${REMOTE_PATH}/routes/api/auth.php
  sudo cp /tmp/api.php ${REMOTE_PATH}/routes/api.php
  sudo cp /tmp/services.php ${REMOTE_PATH}/config/services.php
  
  # Actualizar composer
  sudo cp /tmp/composer.json ${REMOTE_PATH}/composer.json
  sudo cp /tmp/composer.lock ${REMOTE_PATH}/composer.lock
REMOTE_COMMANDS

echo -e "${GREEN}✔ Archivos instalados.${NC}\n"

# ── Paso 3: Instalar dependencias de composer dentro del contenedor Docker ──
echo -e "${YELLOW}[3/5] Ejecutando composer install en el contenedor Docker...${NC}"

$SSH_CMD bash << REMOTE_COMMANDS
  cd ${REMOTE_PATH}
  
  # Detectar si corre en Docker o directo
  if docker ps --format '{{.Names}}' 2>/dev/null | grep -q 'senselab\|app\|php'; then
    APP_CONTAINER=\$(docker ps --format '{{.Names}}' | grep -E 'senselab.*app|php' | head -1)
    echo "Usando contenedor Docker: \$APP_CONTAINER"
    docker exec \$APP_CONTAINER composer install --no-dev --optimize-autoloader --ignore-platform-reqs --no-interaction
  else
    echo "Ejecutando composer directamente..."
    sudo composer install --no-dev --optimize-autoloader --ignore-platform-reqs --no-interaction
  fi
REMOTE_COMMANDS

echo -e "${GREEN}✔ Dependencias instaladas.${NC}\n"

# ── Paso 4: Configurar variables de entorno de Google OAuth ─────────────────
echo -e "${YELLOW}[4/5] Verificando variables de entorno en el servidor...${NC}"

$SSH_CMD bash << REMOTE_COMMANDS
  if grep -q 'GOOGLE_CLIENT_ID' ${REMOTE_PATH}/.env; then
    echo "✔ Variables de Google OAuth ya existen en .env"
  else
    echo "Añadiendo placeholders de Google OAuth a .env..."
    echo "" | sudo tee -a ${REMOTE_PATH}/.env
    echo "# Google OAuth Configuration (SSO / Socialite)" | sudo tee -a ${REMOTE_PATH}/.env
    echo "GOOGLE_CLIENT_ID=your-google-client-id" | sudo tee -a ${REMOTE_PATH}/.env
    echo "GOOGLE_CLIENT_SECRET=your-google-client-secret" | sudo tee -a ${REMOTE_PATH}/.env
    echo "GOOGLE_REDIRECT_URI=https://api.scisenselab.com/api/auth/google/callback" | sudo tee -a ${REMOTE_PATH}/.env
    echo "" | sudo tee -a ${REMOTE_PATH}/.env
    echo "# Apple OAuth Configuration (SSO)" | sudo tee -a ${REMOTE_PATH}/.env
    echo "APPLE_CLIENT_ID=com.scisenselab.service" | sudo tee -a ${REMOTE_PATH}/.env
    echo "APPLE_REDIRECT_URI=https://api.scisenselab.com/api/auth/apple/callback" | sudo tee -a ${REMOTE_PATH}/.env
  fi
REMOTE_COMMANDS

echo -e "${GREEN}✔ Variables de entorno verificadas.${NC}\n"

# ── Paso 5: Limpiar caches de Laravel ──────────────────────────────────────
echo -e "${YELLOW}[5/5] Limpiando caches de Laravel...${NC}"

$SSH_CMD bash << REMOTE_COMMANDS
  cd ${REMOTE_PATH}
  
  if docker ps --format '{{.Names}}' 2>/dev/null | grep -q 'senselab\|app\|php'; then
    APP_CONTAINER=\$(docker ps --format '{{.Names}}' | grep -E 'senselab.*app|php' | head -1)
    docker exec \$APP_CONTAINER php artisan config:clear
    docker exec \$APP_CONTAINER php artisan route:clear
    docker exec \$APP_CONTAINER php artisan cache:clear
    docker exec \$APP_CONTAINER php artisan config:cache
    docker exec \$APP_CONTAINER php artisan route:cache
    echo "✔ Caches limpiados y regenerados en el contenedor \$APP_CONTAINER"
  else
    sudo php artisan config:clear
    sudo php artisan route:clear
    sudo php artisan cache:clear
    sudo php artisan config:cache
    sudo php artisan route:cache
    echo "✔ Caches limpiados y regenerados directamente"
  fi
REMOTE_COMMANDS

echo -e "${GREEN}✔ Caches actualizados.${NC}\n"

# ── Verificación final ──────────────────────────────────────────────────────
echo -e "${YELLOW}Verificando rutas OAuth en producción...${NC}"
sleep 2

GOOGLE_RESULT=$(curl -s -o /dev/null -w "%{http_code}" "https://api.scisenselab.com/api/auth/google/redirect?redirect_origin=https://scisenselab.com")
APPLE_RESULT=$(curl -s -o /dev/null -w "%{http_code}" "https://api.scisenselab.com/api/auth/apple/redirect?redirect_origin=https://scisenselab.com")

echo ""
echo -e "${GREEN}============================================${NC}"
echo -e "${GREEN}  Resultado del Despliegue${NC}"
echo -e "${GREEN}============================================${NC}"

if [[ "$GOOGLE_RESULT" == "302" ]]; then
  echo -e "${GREEN}✔ Google OAuth: OK (HTTP ${GOOGLE_RESULT})${NC}"
else
  echo -e "${RED}✗ Google OAuth: FALLO (HTTP ${GOOGLE_RESULT}) — Configurar GOOGLE_CLIENT_ID en .env${NC}"
fi

if [[ "$APPLE_RESULT" == "302" ]]; then
  echo -e "${GREEN}✔ Apple OAuth:  OK (HTTP ${APPLE_RESULT})${NC}"
else
  echo -e "${RED}✗ Apple OAuth:  FALLO (HTTP ${APPLE_RESULT}) — Verificar APPLE_CLIENT_ID en .env${NC}"
fi

echo ""
echo -e "${YELLOW}IMPORTANTE: Actualiza las credenciales reales en el archivo .env del servidor:${NC}"
echo -e "  GOOGLE_CLIENT_ID=<tu-client-id-real>"
echo -e "  GOOGLE_CLIENT_SECRET=<tu-secreto-real>"
echo -e "  APPLE_CLIENT_ID=<tu-service-id-de-apple>"
echo ""
echo -e "${GREEN}✔ Despliegue completado.${NC}"
