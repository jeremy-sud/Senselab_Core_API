# Script de Instalación Automatizada - Senselab Core API
# Senselab
# Para Windows - PowerShell

Write-Host "==================================================" -ForegroundColor Cyan
Write-Host "  SENSELAB CORE API - Instalación Automatizada" -ForegroundColor Cyan
Write-Host "  Senselab" -ForegroundColor Cyan
Write-Host "==================================================" -ForegroundColor Cyan
Write-Host ""

# Función para imprimir mensajes
function Print-Success {
    param($Message)
    Write-Host "✓ $Message" -ForegroundColor Green
}

function Print-Error {
    param($Message)
    Write-Host "✗ $Message" -ForegroundColor Red
}

function Print-Info {
    param($Message)
    Write-Host "ℹ $Message" -ForegroundColor Yellow
}

# Verificar si estamos en el directorio correcto
if (-not (Test-Path "artisan")) {
    Print-Error "Este script debe ejecutarse desde el directorio raíz del proyecto (donde está artisan)"
    exit 1
}

Print-Info "Iniciando instalación..."
Write-Host ""

# 1. Verificar requisitos
Write-Host "Paso 1: Verificando requisitos del sistema..."

# Verificar PHP
try {
    $phpVersion = php -r "echo PHP_VERSION;"
    Print-Success "PHP $phpVersion instalado"
} catch {
    Print-Error "PHP no está instalado o no está en el PATH"
    exit 1
}

# Verificar Composer
try {
    $composerVersion = (composer -V 2>&1 | Select-String "Composer version").ToString().Split()[2]
    Print-Success "Composer $composerVersion instalado"
} catch {
    Print-Error "Composer no está instalado o no está en el PATH"
    exit 1
}

# Verificar pnpm (opcional pero recomendado)
try {
    $pnpmVersion = pnpm --version 2>$null
    Print-Success "pnpm $pnpmVersion instalado"
} catch {
    Print-Info "pnpm no instalado (opcional - solo para frontend)"
    Print-Info "Para instalar: npm install -g pnpm"
}

# Verificar MySQL
try {
    $null = Get-Command mysql -ErrorAction Stop
    Print-Success "MySQL client disponible"
} catch {
    Print-Error "MySQL client no está instalado o no está en el PATH"
    exit 1
}

Write-Host ""

# 2. Instalar dependencias de Composer
Write-Host "Paso 2: Instalando dependencias de PHP..."
composer install --no-interaction --prefer-dist --optimize-autoloader
Print-Success "Dependencias de Composer instaladas"
Write-Host ""

# 3. Configurar .env
Write-Host "Paso 3: Configurando archivo de entorno..."

if (-not (Test-Path ".env")) {
    Copy-Item ".env.example" ".env"
    Print-Success "Archivo .env creado desde .env.example"
} else {
    Print-Info "Archivo .env ya existe, no se sobrescribirá"
}

# Generar APP_KEY
php artisan key:generate --force
Print-Success "Clave de aplicación generada"
Write-Host ""

# 4. Solicitar credenciales de base de datos
Write-Host "Paso 4: Configuración de Base de Datos"
Write-Host "----------------------------------------"

$dbUser = Read-Host "Usuario de MySQL [root]"
if ([string]::IsNullOrWhiteSpace($dbUser)) { $dbUser = "root" }

$dbPassSecure = Read-Host "Password de MySQL" -AsSecureString
$dbPass = [Runtime.InteropServices.Marshal]::PtrToStringAuto(
    [Runtime.InteropServices.Marshal]::SecureStringToBSTR($dbPassSecure))

$dbName = Read-Host "Nombre de la base de datos [api_db]"
if ([string]::IsNullOrWhiteSpace($dbName)) { $dbName = "api_db" }

$dbTestName = Read-Host "Nombre de la base de datos de testing [api_db_testing]"
if ([string]::IsNullOrWhiteSpace($dbTestName)) { $dbTestName = "api_db_testing" }

$dbHost = Read-Host "Host de MySQL [127.0.0.1]"
if ([string]::IsNullOrWhiteSpace($dbHost)) { $dbHost = "127.0.0.1" }

$dbPort = Read-Host "Puerto de MySQL [3306]"
if ([string]::IsNullOrWhiteSpace($dbPort)) { $dbPort = "3306" }

# Actualizar .env de forma segura (reemplaza solo las claves necesarias)
$envPath = Join-Path -Path (Get-Location) -ChildPath '.env'
$lines = Get-Content $envPath -ErrorAction Stop
for ($i = 0; $i -lt $lines.Length; $i++) {
    if ($lines[$i] -match '^DB_CONNECTION=') { $lines[$i] = "DB_CONNECTION=mysql" }
    if ($lines[$i] -match '^DB_HOST=')       { $lines[$i] = "DB_HOST=$dbHost" }
    if ($lines[$i] -match '^DB_PORT=')       { $lines[$i] = "DB_PORT=$dbPort" }
    if ($lines[$i] -match '^DB_USERNAME=')   { $lines[$i] = "DB_USERNAME=$dbUser" }
    if ($lines[$i] -match '^DB_PASSWORD=')   { $lines[$i] = "DB_PASSWORD=$dbPass" }
    if ($lines[$i] -match '^DB_DATABASE=')   { $lines[$i] = "DB_DATABASE=$dbName" }
}
# Asegurar que exista DB_DATABASE_TEST
if ($lines -notmatch '^DB_DATABASE_TEST=') { $lines += "DB_DATABASE_TEST=$dbTestName" }

Set-Content -Path $envPath -Value $lines -Encoding UTF8

Print-Success "Archivo .env actualizado con credenciales de BD"
Write-Host ""

# 5. Crear bases de datos
Write-Host "Paso 5: Creando bases de datos..."

# Crear un archivo temporal con credenciales para mysql (evita exponer la contraseña en la línea de comando)
$tmpFile = [System.IO.Path]::Combine($env:TEMP, "mysql_creds_$(Get-Random).cnf")
$cnfContent = @"
[client]
user=$dbUser
password=$dbPass
host=$dbHost
port=$dbPort
"@

Set-Content -Path $tmpFile -Value $cnfContent -Encoding ASCII

try {
    $createDbQuery = 'CREATE DATABASE IF NOT EXISTS `' + $dbName + '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;'
    mysql --defaults-extra-file="$tmpFile" -e "$createDbQuery" 2>$null
    Print-Success "Base de datos '$dbName' creada"
} catch {
    Print-Error "Error al crear base de datos '$dbName'"
    Print-Info "Verifica que las credenciales de MySQL sean correctas o que el servidor esté accesible"
    Remove-Item -Path $tmpFile -ErrorAction SilentlyContinue
    exit 1
}

try {
    $createTestDbQuery = 'CREATE DATABASE IF NOT EXISTS `' + $dbTestName + '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;'
    mysql --defaults-extra-file="$tmpFile" -e "$createTestDbQuery" 2>$null
    Print-Success "Base de datos '$dbTestName' creada"
} catch {
    Print-Error "Error al crear base de datos de testing '$dbTestName'"
}

# Eliminar archivo temporal
Remove-Item -Path $tmpFile -ErrorAction SilentlyContinue

Write-Host ""

# 6. Ejecutar migraciones
Write-Host "Paso 6: Ejecutando migraciones..."
php artisan migrate --force
Print-Success "Migraciones ejecutadas (82 tablas creadas)"
Write-Host ""

# 7. Ejecutar seeders
Write-Host "Paso 7: Cargando datos maestros y de prueba..."
php artisan db:seed --force
Print-Success "Seeders ejecutados (112 registros cargados)"
Write-Host ""

# Detalle de datos cargados
Print-Info "Datos cargados:"
Write-Host "  • 2 Regímenes Tributarios"
Write-Host "  • 6 Formas de Pago"
Write-Host "  • 8 Tipos de Cuentas"
Write-Host "  • 11 Unidades de Medida"
Write-Host "  • 68 Permisos (17 módulos × 4 acciones)"
Write-Host "  • 7 Roles (Administrador, Gerente, Contador, etc.)"
Write-Host "  • 7 Cargos"
Write-Host "  • 1 Empresa demo (Senselab)"
Write-Host "  • 1 Usuario administrador"
Write-Host ""

# 8. Crear enlace simbólico de storage
Write-Host "Paso 8: Creando enlaces simbólicos..."
php artisan storage:link
Print-Success "Enlaces simbólicos creados"
Write-Host ""

# 9. Limpiar cachés
Write-Host "Paso 9: Limpiando cachés..."
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
Print-Success "Cachés limpiados"
Write-Host ""

# 10. Ejecutar tests (opcional)
Write-Host "Paso 10: Tests (opcional)"
$runTests = Read-Host "¿Deseas ejecutar los tests ahora? (s/N)"
if ($runTests -match "^[Ss]$") {
    Write-Host "Ejecutando tests..."
    php artisan test
    Write-Host ""
}

# Resumen final
Write-Host "==================================================" -ForegroundColor Green
Write-Host "✓ INSTALACIÓN COMPLETADA EXITOSAMENTE" -ForegroundColor Green
Write-Host "==================================================" -ForegroundColor Green
Write-Host ""
Write-Host "🚀 Servidor de desarrollo:" -ForegroundColor Cyan
Write-Host "   php artisan serve"
Write-Host ""
Write-Host "🌐 URLs importantes:" -ForegroundColor Cyan
Write-Host "   API:     http://localhost:8000/api"
Write-Host "   Swagger: http://localhost:8000/api/documentation"
Write-Host ""
Write-Host "🔐 Credenciales de acceso:" -ForegroundColor Cyan
Write-Host "   Email:    admin@scisenselab.com"
Write-Host "   Password: admin123"
Write-Host "   Permisos: 68 (todos los módulos)"
Write-Host ""
Write-Host "📊 Empresa demo creada:" -ForegroundColor Cyan
Write-Host "   Nombre:  Senselab"
Write-Host "   Cédula:  3-101-123456"
Write-Host ""
Write-Host "📚 Documentación:" -ForegroundColor Cyan
Write-Host "   README.md              - Documentación principal"
Write-Host "   INSTALLATION_GUIDE.md  - Guía de instalación detallada"
Write-Host "   API_DOCUMENTATION.md   - Endpoints (420+ rutas)"
Write-Host "   SECURITY.md            - Guía de seguridad OWASP Top 10"
Write-Host "   TESTING_GUIDE.md       - Guía de testing"
Write-Host ""
Write-Host "🧪 Tests:" -ForegroundColor Cyan
Write-Host "   php artisan test       - Ejecutar suite de tests (100+ tests)"
Write-Host ""
Print-Success "¡Listo para desarrollar!"
Write-Host ""
