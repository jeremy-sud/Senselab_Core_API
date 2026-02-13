#!/bin/bash
# Script para analizar y categorizar errores de PHPStan

cd /home/dawnweaber/Workspace/Ursol-CAST-API

echo "=== ANALISIS PHPSTAN NIVEL 8 ==="
echo "Ejecutando análisis de errores..."

# Ejecutar PHPStan y guardar resultado
./vendor/bin/phpstan analyze --level=8 app/ --no-progress -q > /tmp/phpstan_errors.txt 2>&1

# Contar errores totales
TOTAL=$(grep -c "error" /tmp/phpstan_errors.txt || echo 0)
echo "Total de líneas: $TOTAL"

# Categorizar por tipo de error
echo ""
echo "=== CATEGORIAS DE ERRORES ==="

# Missing return type
echo "Missing return types:"
grep -c "has no return type" /tmp/phpstan_errors.txt || echo 0

# Missing parameter type
echo "Missing parameter types:"
grep -c "has no parameter type" /tmp/phpstan_errors.txt || echo 0

# Undefined variable/property
echo "Undefined properties/variables:"
grep -c "Undefined variable\|Undefined property" /tmp/phpstan_errors.txt || echo 0

# Type mismatch
echo "Type mismatch errors:"
grep -c "expects.*but.*given" /tmp/phpstan_errors.txt || echo 0

# Access on null
echo "Access on null/mixed:"
grep -c "Access on null\|Access on mixed" /tmp/phpstan_errors.txt || echo 0

echo ""
echo "Primeras 20 errores:"
head -20 /tmp/phpstan_errors.txt
