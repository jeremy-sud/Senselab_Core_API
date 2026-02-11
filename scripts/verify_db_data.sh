#!/bin/bash
# Script de Verificación de Datos en Bases de Datos
# Verifica que todas las tablas principales tengan datos correctos

set -e

echo "════════════════════════════════════════════════════════"
echo "🔍 VERIFICACIÓN DE DATOS - BASES DE DATOS URSOL"
echo "════════════════════════════════════════════════════════"
echo ""

HOST="127.0.0.1"
PORT="33061"
USER="ursol_user"
PASS="ursol_password"

# Colores
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# ============================================
# FUNCIÓN: Verificar tabla
# ============================================
verificar_tabla() {
    local db=$1
    local tabla=$2
    local min_registros=${3:-1}
    
    count=$(mysql -h "$HOST" -P "$PORT" -u "$USER" -p"$PASS" "$db" -N -e "SELECT COUNT(*) FROM $tabla;" 2>/dev/null || echo "ERROR")
    
    if [ "$count" = "ERROR" ]; then
        echo -e "${RED}✗${NC} $tabla: ERROR DE CONEXIÓN"
        return 1
    fi
    
    if [ "$count" -ge "$min_registros" ]; then
        echo -e "${GREEN}✓${NC} $tabla: $count registros"
        return 0
    else
        echo -e "${YELLOW}⚠${NC} $tabla: $count registro(s) (mínimo esperado: $min_registros)"
        return 2
    fi
}

# ============================================
# API_DB (PRODUCCIÓN)
# ============================================
echo "📊 api_db (PRODUCCIÓN)"
echo "─────────────────────────────────────"

unidades=$(mysql -h "$HOST" -P "$PORT" -u "$USER" -p"$PASS" api_db -N -e "SELECT COUNT(*) FROM unidades_medida;" 2>/dev/null)
categorias=$(mysql -h "$HOST" -P "$PORT" -u "$USER" -p"$PASS" api_db -N -e "SELECT COUNT(*) FROM categorias_productos;" 2>/dev/null)
marcas=$(mysql -h "$HOST" -P "$PORT" -u "$USER" -p"$PASS" api_db -N -e "SELECT COUNT(*) FROM marcas;" 2>/dev/null)
productos=$(mysql -h "$HOST" -P "$PORT" -u "$USER" -p"$PASS" api_db -N -e "SELECT COUNT(*) FROM productos;" 2>/dev/null)
clientes=$(mysql -h "$HOST" -P "$PORT" -u "$USER" -p"$PASS" api_db -N -e "SELECT COUNT(*) FROM clientes;" 2>/dev/null)
proveedores=$(mysql -h "$HOST" -P "$PORT" -u "$USER" -p"$PASS" api_db -N -e "SELECT COUNT(*) FROM proveedores;" 2>/dev/null)
empleados=$(mysql -h "$HOST" -P "$PORT" -u "$USER" -p"$PASS" api_db -N -e "SELECT COUNT(*) FROM empleados;" 2>/dev/null)
almacenes=$(mysql -h "$HOST" -P "$PORT" -u "$USER" -p"$PASS" api_db -N -e "SELECT COUNT(*) FROM almacenes;" 2>/dev/null)

verificar_tabla "api_db" "unidades_medida" 5
verificar_tabla "api_db" "categorias_productos" 3
verificar_tabla "api_db" "marcas" 5
verificar_tabla "api_db" "productos" 5
verificar_tabla "api_db" "clientes" 3
verificar_tabla "api_db" "proveedores" 3
verificar_tabla "api_db" "empleados" 3
verificar_tabla "api_db" "almacenes" 2

total_prod=$(( $unidades + $categorias + $marcas + $productos + $clientes + $proveedores + $empleados + $almacenes ))
echo "─────────────────────────────────────"
echo -e "${GREEN}TOTAL api_db: $total_prod registros${NC}"
echo ""

# ============================================
# API_DB_TESTING (TESTING)
# ============================================
echo "📊 api_db_testing (TESTING)"
echo "─────────────────────────────────────"

unidades_t=$(mysql -h "$HOST" -P "$PORT" -u "$USER" -p"$PASS" api_db_testing -N -e "SELECT COUNT(*) FROM unidades_medida;" 2>/dev/null)
categorias_t=$(mysql -h "$HOST" -P "$PORT" -u "$USER" -p"$PASS" api_db_testing -N -e "SELECT COUNT(*) FROM categorias_productos;" 2>/dev/null)
marcas_t=$(mysql -h "$HOST" -P "$PORT" -u "$USER" -p"$PASS" api_db_testing -N -e "SELECT COUNT(*) FROM marcas;" 2>/dev/null)
productos_t=$(mysql -h "$HOST" -P "$PORT" -u "$USER" -p"$PASS" api_db_testing -N -e "SELECT COUNT(*) FROM productos;" 2>/dev/null)
clientes_t=$(mysql -h "$HOST" -P "$PORT" -u "$USER" -p"$PASS" api_db_testing -N -e "SELECT COUNT(*) FROM clientes;" 2>/dev/null)
proveedores_t=$(mysql -h "$HOST" -P "$PORT" -u "$USER" -p"$PASS" api_db_testing -N -e "SELECT COUNT(*) FROM proveedores;" 2>/dev/null)
empleados_t=$(mysql -h "$HOST" -P "$PORT" -u "$USER" -p"$PASS" api_db_testing -N -e "SELECT COUNT(*) FROM empleados;" 2>/dev/null)
almacenes_t=$(mysql -h "$HOST" -P "$PORT" -u "$USER" -p"$PASS" api_db_testing -N -e "SELECT COUNT(*) FROM almacenes;" 2>/dev/null)

verificar_tabla "api_db_testing" "unidades_medida" 3
verificar_tabla "api_db_testing" "categorias_productos" 3
verificar_tabla "api_db_testing" "marcas" 3
verificar_tabla "api_db_testing" "productos" 3
verificar_tabla "api_db_testing" "clientes" 2
verificar_tabla "api_db_testing" "proveedores" 2
verificar_tabla "api_db_testing" "empleados" 2
verificar_tabla "api_db_testing" "almacenes" 2

total_test=$(( $unidades_t + $categorias_t + $marcas_t + $productos_t + $clientes_t + $proveedores_t + $empleados_t + $almacenes_t ))
echo "─────────────────────────────────────"
echo -e "${GREEN}TOTAL api_db_testing: $total_test registros${NC}"
echo ""

# ============================================
# RESUMEN FINAL
# ============================================
echo "════════════════════════════════════════════════════════"
echo "📋 RESUMEN FINAL"
echo "════════════════════════════════════════════════════════"
echo ""

if [ "$total_prod" -gt 30 ] && [ "$total_test" -gt 20 ]; then
    echo -e "${GREEN}✅ VERIFICACIÓN EXITOSA${NC}"
    echo ""
    echo "api_db:         $total_prod registros ✓"
    echo "api_db_testing: $total_test registros ✓"
    echo ""
    echo "Las bases de datos están listas para testing."
else
    echo -e "${YELLOW}⚠️  VERIFICACIÓN CON ADVERTENCIAS${NC}"
    echo ""
    echo "api_db:         $total_prod registros"
    echo "api_db_testing: $total_test registros"
    echo ""
    echo "Verifique si necesita datos adicionales."
fi

echo ""
echo "════════════════════════════════════════════════════════"
echo "✓ Verificación completada"
echo "════════════════════════════════════════════════════════"
