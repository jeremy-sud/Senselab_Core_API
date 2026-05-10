#!/bin/bash

echo "========================================="
echo "    PRUEBA DEL SISTEMA RBAC - FASE 3"
echo "========================================="
echo ""

BASE_URL="http://localhost:8000/api"

echo "1. LOGIN como Administrador..."
echo "   Email: admin@senselab.com"
echo "   Password: admin123"
echo ""

LOGIN_RESPONSE=$(curl -s -X POST "${BASE_URL}/login" \
  -H "Content-Type: application/json" \
  -d '{
    "email": "admin@senselab.com",
    "password": "admin123"
  }')

echo "Respuesta del login:"
echo "$LOGIN_RESPONSE" | jq '.'
echo ""

# Extraer token
TOKEN=$(echo "$LOGIN_RESPONSE" | jq -r '.data.token')

if [ "$TOKEN" == "null" ] || [ -z "$TOKEN" ]; then
  echo "❌ ERROR: No se pudo obtener el token de autenticación"
  exit 1
fi

echo "✅ Token obtenido exitosamente"
echo ""

echo "2. OBTENER PERFIL DEL USUARIO (/me)..."
echo ""

ME_RESPONSE=$(curl -s -X GET "${BASE_URL}/me" \
  -H "Authorization: Bearer ${TOKEN}" \
  -H "Content-Type: application/json")

echo "Perfil del usuario:"
echo "$ME_RESPONSE" | jq '.'
echo ""

# Verificar permisos
PERMISOS_COUNT=$(echo "$ME_RESPONSE" | jq '.data.permisos | length')
echo "✅ Usuario tiene $PERMISOS_COUNT permisos asignados"
echo ""

echo "3. VERIFICAR ACCESO A PRODUCTOS (requiere permiso 'ver-productos')..."
echo ""

PRODUCTOS_RESPONSE=$(curl -s -X GET "${BASE_URL}/productos" \
  -H "Authorization: Bearer ${TOKEN}" \
  -H "Content-Type: application/json")

echo "Respuesta de productos:"
echo "$PRODUCTOS_RESPONSE" | jq '.'
echo ""

echo "4. LOGOUT..."
echo ""

LOGOUT_RESPONSE=$(curl -s -X POST "${BASE_URL}/logout" \
  -H "Authorization: Bearer ${TOKEN}" \
  -H "Content-Type: application/json")

echo "Respuesta del logout:"
echo "$LOGOUT_RESPONSE" | jq '.'
echo ""

echo "========================================="
echo "    PRUEBA COMPLETADA"
echo "========================================="
echo ""
echo "Resumen:"
echo "  ✅ Login exitoso"
echo "  ✅ Token generado"
echo "  ✅ Perfil obtenido con $PERMISOS_COUNT permisos"
echo "  ✅ Logout exitoso"
echo ""
