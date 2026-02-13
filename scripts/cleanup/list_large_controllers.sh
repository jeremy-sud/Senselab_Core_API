#!/bin/bash

# Script para listar controladores PHP ordenados por número de líneas
# Uso: ./scripts/cleanup/list_large_controllers.sh [umbral_lineas]

THRESHOLD=${1:-400}  # Umbral por defecto: 400 líneas

echo "📊 Listando controladores PHP ordenados por número de líneas"
echo "   (Destacando aquellos con más de $THRESHOLD líneas)"
echo "---------------------------------------------------"

# Encontrar todos los controladores y contar sus líneas
echo ""
echo "🔴 CONTROLADORES CRÍTICOS (>${THRESHOLD} líneas) - Prioridad de refactorización:"
echo ""

find app/Http/Controllers -name "*.php" -type f -exec wc -l {} \; 2>/dev/null | \
    sort -rn | \
    awk -v threshold="$THRESHOLD" '
    BEGIN { 
        critical = 0; 
        warning = 0; 
        ok = 0;
        total_lines = 0;
    }
    {
        lines = $1
        file = $2
        total_lines += lines
        
        if (lines > threshold) {
            critical++
            printf "   %4d líneas  →  %s\n", lines, file
        } else if (lines > 200) {
            warning++
        } else {
            ok++
        }
    }
    END {
        printf "\n---------------------------------------------------\n"
        printf "📈 RESUMEN:\n"
        printf "   🔴 Críticos (>%d líneas):   %d controladores\n", threshold, critical
        printf "   🟡 Medianos (200-%d líneas): %d controladores\n", threshold, warning
        printf "   🟢 Pequeños (<200 líneas):   %d controladores\n", ok
        printf "   📊 Total de líneas:          %d\n", total_lines
        printf "   📁 Total de controladores:   %d\n", critical + warning + ok
    }'

echo ""
echo "💡 Recomendación: Refactorizar los controladores críticos siguiendo el patrón:"
echo "   Controller (thin) → Service (business logic) → DTO (data transfer)"
echo ""
echo "---------------------------------------------------"
echo "Análisis completado."
