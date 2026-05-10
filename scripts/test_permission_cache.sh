#!/bin/bash

# Script de Testing para Sistema de Cache de Permisos
# Senselab Core API - Sprint 3
# Uso: ./test_permission_cache.sh

# Colores para output
GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

# Banner
echo -e "${BLUE}═══════════════════════════════════════════════════════════${NC}"
echo -e "${BLUE}   Testing Sistema de Cache de Permisos - Sprint 3       ${NC}"
echo -e "${BLUE}═══════════════════════════════════════════════════════════${NC}"
echo ""

# Verificar que estamos en el directorio correcto
if [ ! -f "artisan" ]; then
    echo -e "${RED}❌ Error: Este script debe ejecutarse desde el directorio raíz del proyecto${NC}"
    exit 1
fi

# 1. Limpiar cache inicial
echo -e "${YELLOW}[1/7]${NC} Limpiando cache inicial..."
php artisan permission:cache clear > /dev/null 2>&1
echo -e "${GREEN}✓${NC} Cache limpiado"
echo ""

# 2. Ver estadísticas iniciales
echo -e "${YELLOW}[2/7]${NC} Estadísticas iniciales del sistema:"
php artisan permission:cache stats
echo ""

# 3. Precalentar cache
echo -e "${YELLOW}[3/7]${NC} Precalentando cache para primeros 50 usuarios..."
php artisan permission:cache warmup --limit=50
echo ""

# 4. Ver estadísticas después de warmup
echo -e "${YELLOW}[4/7]${NC} Estadísticas después de warmup:"
php artisan permission:cache stats
echo ""

# 5. Testing con Tinker - Verificar cache
echo -e "${YELLOW}[5/7]${NC} Testing de cache con Tinker..."
cat << 'EOF' | php artisan tinker
// Obtener primer usuario
$usuario = App\Models\Usuario::activos()->first();

if (!$usuario) {
    echo "\n❌ No hay usuarios activos en la base de datos\n";
    exit(1);
}

echo "\n✓ Usuario encontrado: {$usuario->nombre}\n";
echo "  ID: {$usuario->id}\n";
echo "  Email: {$usuario->email}\n\n";

// Test 1: Obtener permisos con cache
echo "Test 1: Obtener permisos cacheados\n";
$start = microtime(true);
$permisos = $usuario->getCachedPermissions();
$time1 = round((microtime(true) - $start) * 1000, 2);
echo "  Permisos encontrados: " . count($permisos) . "\n";
echo "  Tiempo: {$time1}ms\n\n";

// Test 2: Segunda llamada (debería ser más rápida por cache)
echo "Test 2: Segunda llamada (cache hit esperado)\n";
$start = microtime(true);
$permisos2 = $usuario->getCachedPermissions();
$time2 = round((microtime(true) - $start) * 1000, 2);
echo "  Permisos encontrados: " . count($permisos2) . "\n";
echo "  Tiempo: {$time2}ms\n";

if ($time2 < $time1) {
    echo "  ✓ CACHE HIT - Segunda llamada más rápida\n";
    $mejora = round((($time1 - $time2) / $time1) * 100, 1);
    echo "  Mejora: {$mejora}%\n\n";
} else {
    echo "  ⚠ Posible cache miss\n\n";
}

// Test 3: Verificar permiso específico
echo "Test 3: Verificar permisos específicos\n";
if (count($permisos) > 0) {
    $primerPermiso = $permisos[0];
    echo "  Verificando: {$primerPermiso}\n";
    
    $start = microtime(true);
    $tiene = $usuario->hasCachedPermission($primerPermiso);
    $time3 = round((microtime(true) - $start) * 1000, 2);
    
    echo "  Resultado: " . ($tiene ? 'SÍ' : 'NO') . "\n";
    echo "  Tiempo: {$time3}ms\n\n";
}

// Test 4: PermissionService
echo "Test 4: Testing PermissionService\n";
$service = app(App\Services\PermissionService::class);

$start = microtime(true);
$userPerms = $service->getUserPermissions($usuario);
$time4 = round((microtime(true) - $start) * 1000, 2);

echo "  Permisos via Service: " . count($userPerms) . "\n";
echo "  Tiempo: {$time4}ms\n\n";

// Test 5: Limpiar y recargar
echo "Test 5: Limpiar cache y recargar\n";
$usuario->clearPermissionCache();
echo "  ✓ Cache limpiado\n";

$start = microtime(true);
$permisos3 = $usuario->getCachedPermissions();
$time5 = round((microtime(true) - $start) * 1000, 2);
echo "  Recargado: " . count($permisos3) . " permisos\n";
echo "  Tiempo: {$time5}ms\n\n";

echo "═══════════════════════════════════════════\n";
echo "RESUMEN DE PERFORMANCE:\n";
echo "═══════════════════════════════════════════\n";
echo "Primera carga:      {$time1}ms\n";
echo "Cache hit:          {$time2}ms\n";
echo "Verificación:       {$time3}ms\n";
echo "Via Service:        {$time4}ms\n";
echo "Después de limpiar: {$time5}ms\n\n";

EOF

echo ""

# 6. Testing de observers
echo -e "${YELLOW}[6/7]${NC} Testing de auto-limpieza (Observers)..."
cat << 'EOF' | php artisan tinker
// Obtener un permiso y modificarlo para activar observer
$permiso = App\Models\Permiso::activos()->first();

if ($permiso) {
    echo "\n✓ Testing observer para Permiso: {$permiso->nombre}\n";
    
    // Obtener roles que tienen este permiso antes
    $rolesBefore = $permiso->roles()->count();
    echo "  Roles con este permiso: {$rolesBefore}\n";
    
    // Simular actualización (esto debería limpiar cache)
    $permiso->touch();
    echo "  ✓ Permiso actualizado (observer debería limpiar cache)\n\n";
}

// Testing observer de Rol
$rol = App\Models\Rol::activos()->first();

if ($rol) {
    echo "✓ Testing observer para Rol: {$rol->nombre}\n";
    
    // Contar usuarios con este rol
    $usersBefore = $rol->usuarios()->count();
    echo "  Usuarios con este rol: {$usersBefore}\n";
    
    // Simular actualización
    $rol->touch();
    echo "  ✓ Rol actualizado (observer debería limpiar cache de usuarios)\n\n";
}

EOF

echo ""

# 7. Estadísticas finales
echo -e "${YELLOW}[7/7]${NC} Estadísticas finales:"
php artisan permission:cache stats
echo ""

# Resumen
echo -e "${BLUE}═══════════════════════════════════════════════════════════${NC}"
echo -e "${GREEN}✓ Testing completado exitosamente${NC}"
echo -e "${BLUE}═══════════════════════════════════════════════════════════${NC}"
echo ""
echo -e "${YELLOW}Próximos pasos:${NC}"
echo "1. Verificar que los tiempos de cache hit sean menores"
echo "2. Ejecutar: php artisan permission:cache warmup"
echo "3. Configurar cron para warmup diario"
echo "4. Monitorear performance en producción"
echo ""
echo -e "${BLUE}Comandos útiles:${NC}"
echo "  php artisan permission:cache clear       # Limpiar cache"
echo "  php artisan permission:cache warmup      # Precalentar cache"
echo "  php artisan permission:cache stats       # Ver estadísticas"
echo ""
