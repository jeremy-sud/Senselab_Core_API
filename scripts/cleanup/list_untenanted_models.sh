#!/bin/bash

# Script para listar modelos que NO implementan el trait BelongsToTenant
# Esto es crítico para la seguridad multi-tenant
# Uso: ./scripts/cleanup/list_untenanted_models.sh

echo "🔐 Analizando modelos para verificar implementación de BelongsToTenant..."
echo "---------------------------------------------------"

# Trait a buscar
TENANT_TRAIT="BelongsToTenant"

# Contadores
TOTAL=0
WITH_TRAIT=0
WITHOUT_TRAIT=0

echo ""
echo "⚠️  MODELOS SIN TRAIT $TENANT_TRAIT (riesgo de fuga de datos cross-tenant):"
echo ""

# Buscar todos los modelos
for model_file in app/Models/*.php; do
    if [ -f "$model_file" ]; then
        TOTAL=$((TOTAL + 1))
        model_name=$(basename "$model_file" .php)
        
        # Verificar si el modelo usa el trait BelongsToTenant
        if grep -q "$TENANT_TRAIT" "$model_file" 2>/dev/null; then
            WITH_TRAIT=$((WITH_TRAIT + 1))
        else
            WITHOUT_TRAIT=$((WITHOUT_TRAIT + 1))
            
            # Verificar si tiene empresa_id (debería tener el trait)
            if grep -q "empresa_id" "$model_file" 2>/dev/null; then
                echo "   🔴 $model_name - tiene empresa_id pero NO tiene BelongsToTenant"
            else
                echo "   🟡 $model_name - no tiene empresa_id (posiblemente no necesita tenant)"
            fi
        fi
    fi
done

echo ""
echo "---------------------------------------------------"
echo "📈 RESUMEN:"
echo "   📁 Total de modelos:          $TOTAL"
echo "   ✅ Con BelongsToTenant:       $WITH_TRAIT"
echo "   ⚠️  Sin BelongsToTenant:       $WITHOUT_TRAIT"
echo ""

# Calcular porcentaje de cobertura
if [ $TOTAL -gt 0 ]; then
    COVERAGE=$((WITH_TRAIT * 100 / TOTAL))
    echo "   📊 Cobertura multi-tenant:    ${COVERAGE}%"
fi

echo ""
echo "💡 Recomendación: Los modelos 🔴 con empresa_id deben implementar BelongsToTenant"
echo "   para asegurar el aislamiento de datos entre tenants."
echo ""
echo "   Trait ubicado en: app/Traits/BelongsToTenant.php"
echo ""
echo "---------------------------------------------------"
echo "Análisis completado."
