#!/bin/bash

# Script para encontrar archivos duplicados o con conflictos de case-sensitivity
# Uso: ./scripts/cleanup/find_duplicates.sh

echo "🔎 Buscando archivos con conflictos de nombres o duplicados..."
echo "---------------------------------------------------"

# Buscar archivos PHP en el directorio app
echo "📂 Buscando en el directorio app/..."

# Encontrar todos los archivos PHP y agrupar por nombre base (case-insensitive)
find app -type f -name "*.php" | while read file; do
    basename=$(basename "$file" .php)
    echo "$basename|$file"
done | sort -t'|' -k1 -f | awk -F'|' '
BEGIN { prev_lower = ""; prev_entry = ""; found = 0 }
{
    current_lower = tolower($1)
    if (current_lower == prev_lower && $1 != prev_name) {
        if (found == 0) {
            print "⚠️  Conflicto de naming (case-sensitivity):"
            print "   - " prev_entry
            found = 1
        }
        print "   - " $2
    } else {
        found = 0
    }
    prev_lower = current_lower
    prev_name = $1
    prev_entry = $2
}'

echo ""
echo "📂 Buscando específicamente patrones conocidos de conflicto..."
echo ""

# Patrones conocidos de conflicto según MapaEstructuralAPIRESTMultitenant.txt
KNOWN_CONFLICTS=(
    "ConsecutivoFE|ConsecutivoFe"
)

for conflict in "${KNOWN_CONFLICTS[@]}"; do
    IFS='|' read -r pattern1 pattern2 <<< "$conflict"
    echo "Buscando: $pattern1 vs $pattern2"
    
    FILES1=$(find app -type f -name "*${pattern1}*" 2>/dev/null)
    FILES2=$(find app -type f -name "*${pattern2}*" 2>/dev/null)
    
    if [ -n "$FILES1" ] || [ -n "$FILES2" ]; then
        echo "   ⚠️  Archivos encontrados:"
        if [ -n "$FILES1" ]; then
            echo "$FILES1" | while read f; do echo "      - $f"; done
        fi
        if [ -n "$FILES2" ]; then
            echo "$FILES2" | while read f; do echo "      - $f"; done
        fi
        echo "   → Recomendación: Unificar a una sola convención de nombre (sugerido: $pattern1)"
    else
        echo "   ✅ No se encontraron conflictos para este patrón"
    fi
    echo ""
done

# Buscar modelos que hacen referencia a clases potencialmente inexistentes
echo "📂 Verificando referencias a clases potencialmente inexistentes..."
echo ""

MISSING_MODELS=(
    "Comprobante"
    "Factura" 
    "InventarioMovimiento"
)

for model in "${MISSING_MODELS[@]}"; do
    REFS=$(grep -rl "use App\\\\Models\\\\$model" app/ 2>/dev/null | head -5)
    if [ -n "$REFS" ]; then
        echo "⚠️  Referencias a modelo '$model' (posiblemente inexistente):"
        echo "$REFS" | while read f; do echo "   - $f"; done
        
        # Verificar si el modelo realmente existe
        if [ ! -f "app/Models/${model}.php" ]; then
            echo "   → Estado: MODELO NO EXISTE"
        fi
    fi
done

echo ""
echo "---------------------------------------------------"
echo "Búsqueda completada."
