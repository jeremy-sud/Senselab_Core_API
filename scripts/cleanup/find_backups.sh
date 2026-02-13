#!/bin/bash

# Script para encontrar archivos de respaldo, temporales o duplicados en el repositorio.
# Uso: ./scripts/cleanup/find_backups.sh

echo "🔎 Buscando archivos de respaldo, temporales o duplicados..."
echo "---------------------------------------------------"

# Patrones comunes para archivos de respaldo/temporales
BACKUP_PATTERNS=(
  ".backup.php"
  ".bak"
  ".old"
  "-copy.php"
  ".php.temp"
  "api.php.backup"
  "VentaController.backup.php"
)

# Directorios a excluir (para evitar ruido en node_modules, vendor, etc.)
EXCLUDE_DIRS="-path ./node_modules -prune -o -path ./vendor -prune -o -path ./storage -prune -o -path ./bootstrap/cache -prune -o"

FOUND_FILES=()

for pattern in "${BACKUP_PATTERNS[@]}"; do
  # Encuentra archivos que coinciden con el patrón, excluyendo directorios comunes
  # La parte -type f asegura que solo se busquen archivos regulares
  mapfile -t MATCHES < <(find . -type f $EXCLUDE_DIRS -name "*$pattern" -print)
  if [ ${#MATCHES[@]} -gt 0 ]; then
    for file in "${MATCHES[@]}"; do
      FOUND_FILES+=("$file")
    done
  fi
done

if [ ${#FOUND_FILES[@]} -gt 0 ]; then
  echo "¡Archivos de respaldo/temporales encontrados!"
  echo "---------------------------------------------------"
  for file in "${FOUND_FILES[@]}"; do
    echo "- $file"
  done
  echo "\nRecomendación: Mueva estos archivos a 'docs/archive' o elimínelos si ya no son necesarios."
else
  echo "🎉 ¡No se encontraron archivos de respaldo/temporales con los patrones definidos!"
fi

echo "---------------------------------------------------"
echo "Búsqueda completada."
