# Guía de Instalación - Senselab Core API

**Para Colaboradores y Nuevos Desarrolladores**  
**Senselab**  
**Última actualización:** 21 de noviembre de 2025

---

## 📋 Tabla de Contenidos

1. [Requisitos Previos](#-requisitos-previos)
2. [Instalación Paso a Paso](#-instalación-paso-a-paso)
3. [Configuración de la Base de Datos](#️-configuración-de-la-base-de-datos)
4. [Verificación de la Instalación](#-verificación-de-la-instalación)
5. [Credenciales de Acceso](#-credenciales-de-acceso)
6. [Problemas Comunes](#-problemas-comunes)
7. [Próximos Pasos](#-próximos-pasos)

---

## 🔧 Requisitos Previos

Antes de comenzar, asegúrate de tener instalado en tu computadora:

### Software Requerido

- **PHP 8.2 o superior** con extensiones:
  - `php-mysql`
  - `php-xml`
  - `php-mbstring`
  - `php-curl`
  - `php-zip`
  - `php-gd`
  
- **Composer 2.x** (Gestor de dependencias PHP)
- **MySQL 8.0 o superior** (Base de datos)
- **Git** (Control de versiones)
- **Node.js y npm** (opcional, para frontend)

### Verificar Requisitos

```bash
# Verificar versión de PHP
php -v
# Debe mostrar: PHP 8.2.x o superior

# Verificar Composer
composer -V
# Debe mostrar: Composer version 2.x

# Verificar MySQL
mysql --version
# Debe mostrar: mysql Ver 8.0.x o superior

# Verificar Git
git --version
# Debe mostrar: git version 2.x
```

---

## 🚀 Instalación Paso a Paso

### Paso 1: Clonar el Repositorio

```bash
# Navegar a tu directorio de trabajo
cd ~/Workspace  # o donde prefieras trabajar

# Clonar el repositorio
git clone https://github.com/SenseLab-dev/Senselab_Core_API.git

# Entrar al directorio del proyecto
cd Senselab_Core_API
```

### Paso 2: Instalar Dependencias de PHP

```bash
# Instalar todas las dependencias de Composer
composer install
```

**Nota:** Este proceso puede tomar 2-5 minutos dependiendo de tu conexión.

### Paso 3: Configurar Variables de Entorno

```bash
# Copiar el archivo de ejemplo
cp .env.example .env

# Generar la clave de aplicación
php artisan key:generate
```

### Paso 4: Configurar el Archivo .env

Abre el archivo `.env` con tu editor favorito y configura los siguientes valores:

```env
# Configuración de la Aplicación
APP_NAME="Senselab Core API"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

# Configuración de Base de Datos
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=api_db
DB_USERNAME=tu_usuario_mysql    # Generalmente: root
DB_PASSWORD=tu_password_mysql   # Tu password de MySQL
```

**IMPORTANTE:** Reemplaza `tu_usuario_mysql` y `tu_password_mysql` con tus credenciales reales de MySQL.

---

## ⚙️ Configuración de la Base de Datos

### Paso 5: Crear la Base de Datos

#### Opción A: Usando MySQL en terminal

```bash
# Conectar a MySQL (te pedirá tu password)
mysql -u root -p

# Dentro de MySQL, ejecutar:
CREATE DATABASE api_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE DATABASE api_db_testing CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
exit;
```

#### Opción B: Usando phpMyAdmin

1. Abrir phpMyAdmin: `http://localhost/phpmyadmin`
2. Click en "Nueva base de datos"
3. Nombre: `api_db`
4. Cotejamiento: `utf8mb4_unicode_ci`
5. Click en "Crear"
6. Repetir para `api_db_testing`

### Paso 6: Ejecutar Migraciones y Seeders

Este paso creará **todas las tablas** y cargará **todos los datos necesarios** (NO necesitas importar ningún SQL):

```bash
# Ejecutar migraciones (crear tablas)
php artisan migrate

# Cargar datos maestros y de prueba
php artisan db:seed
```

**¿Qué datos se cargarán automáticamente?**

✅ **Datos Maestros (96 registros):**
- 2 Regímenes Tributarios (Simplificado, Tradicional)
- 6 Formas de Pago (Efectivo, Tarjeta, Transferencia, etc.)
- 8 Tipos de Cuentas (Activo, Pasivo, etc.)
- 11 Unidades de Medida (Unidad, Kilogramo, Litro, etc.)
- 68 Permisos (17 módulos × 4 acciones: crear, leer, actualizar, eliminar)
- 7 Roles (Administrador, Gerente, Contador, Vendedor, Comprador, Bodeguero, Usuario)

✅ **Datos de Prueba (16 registros):**
- 7 Cargos (Gerente General, Contador, Vendedor, etc.)
- 1 Empresa demo: "Senselab" (cédula: 3-101-123456)
- 1 Sucursal: "Casa Matriz"
- 1 Usuario administrador con todos los permisos

**Total:** 112 registros listos para usar

### Paso 7: Crear Enlaces Simbólicos (Storage)

```bash
# Crear enlace para archivos públicos
php artisan storage:link
```

---

## ✅ Verificación de la Instalación

### Paso 8: Iniciar el Servidor de Desarrollo

```bash
# Iniciar servidor Laravel
php artisan serve
```

Deberías ver:
```
INFO  Server running on [http://127.0.0.1:8000].

Press Ctrl+C to stop the server
```

### Paso 9: Probar la API

#### A. Verificar que el servidor responde

Abre tu navegador y visita:
```
http://localhost:8000
```

Deberías ver la página de bienvenida de Laravel.

#### B. Probar el endpoint de login

**Opción 1: Usar Postman o Insomnia**

```http
POST http://localhost:8000/api/auth/login
Content-Type: application/json

{
    "email": "admin@senselab.com",
    "password": "admin123"
}
```

**Opción 2: Usar cURL en terminal**

```bash
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@senselab.com","password":"admin123"}'
```

**Respuesta esperada (200 OK):**

```json
{
    "message": "Login exitoso",
    "token": "1|aBcDeFgHiJkLmNoPqRsTuVwXyZ1234567890...",
    "usuario": {
        "id": 1,
        "nombre": "Administrador del Sistema",
        "email": "admin@senselab.com",
        "empresa_id": 1,
        "activo": true
    },
    "permisos": [
        "empresas.crear",
        "empresas.leer",
        "productos.crear",
        ... (68 permisos en total)
    ]
}
```

#### C. Probar endpoint protegido

Usa el token obtenido en el paso anterior:

```bash
curl -X GET http://localhost:8000/api/auth/user \
  -H "Authorization: Bearer TU_TOKEN_AQUI" \
  -H "Accept: application/json"
```

### Paso 10: Ejecutar Tests

```bash
# Ejecutar toda la suite de tests
php artisan test

# Debería mostrar:
# Tests:    81 total
# Passing:  44 (54%)
# Failing:  37 (46%) - en corrección
# Duration: ~7-11 segundos
```

### Paso 11: Ver Documentación Swagger

Abre tu navegador en:
```
http://localhost:8000/api/documentation
```

Aquí podrás:
- ✅ Ver todos los endpoints documentados
- ✅ Probar endpoints interactivamente
- ✅ Ver schemas y ejemplos de datos

---

## 🔐 Credenciales de Acceso

### Usuario Administrador (Pre-creado)

```
Email:    admin@senselab.com
Password: admin123
Permisos: 68 (todos los permisos del sistema)
```

**Usar este usuario para:**
- Pruebas iniciales de la API
- Crear otros usuarios y asignar roles
- Acceso completo a todos los módulos

### Empresa Demo (Pre-creada)

```
Nombre:              Senselab
Cédula Jurídica:     3-101-123456
Régimen Tributario:  Régimen Simplificado
Sucursal:            Casa Matriz
```

### Base de Datos

```
Base de Datos Producción:  api_db
Base de Datos Testing:     api_db_testing
Usuario:                   tu_usuario_mysql (configurado en .env)
```

---

## 🐛 Problemas Comunes

### Error: "Base de datos no existe"

**Problema:** `SQLSTATE[HY000] [1049] Unknown database 'api_db'`

**Solución:**
```bash
mysql -u root -p -e "CREATE DATABASE api_db;"
```

### Error: "Access denied for user"

**Problema:** `SQLSTATE[HY000] [1045] Access denied for user 'root'@'localhost'`

**Solución:**
1. Verifica tu password de MySQL
2. Actualiza el archivo `.env`:
   ```env
   DB_USERNAME=root
   DB_PASSWORD=tu_password_real
   ```

### Error: "Specified key was too long"

**Problema:** Error al ejecutar migraciones

**Solución:**
```bash
# Limpiar caché
php artisan config:clear
php artisan cache:clear

# Ejecutar migraciones nuevamente
php artisan migrate:fresh --seed
```

### Error: "Class not found"

**Problema:** `Class 'App\Models\X' not found`

**Solución:**
```bash
# Regenerar autoload
composer dump-autoload

# Limpiar caché de configuración
php artisan config:clear
php artisan cache:clear
```

### Puerto 8000 ya en uso

**Problema:** `Address already in use`

**Solución:**
```bash
# Usar otro puerto
php artisan serve --port=8001

# O detener el proceso en 8000
lsof -ti:8000 | xargs kill
```

### Tests fallan con error de base de datos

**Problema:** Tests no pueden conectar a `api_db_testing`

**Solución:**
```bash
# Crear base de datos de testing
mysql -u root -p -e "CREATE DATABASE api_db_testing;"

# Ejecutar tests nuevamente
php artisan test
```

---

## 📚 Próximos Pasos

Una vez que tengas todo funcionando:

### 1. Explorar la Documentación

- 📖 [README.md](README.md) - Documentación principal
- 🔧 [API_DOCUMENTATION.md](API_DOCUMENTATION.md) - Endpoints completos (413 rutas)
- 🧪 [TESTING_GUIDE.md](TESTING_GUIDE.md) - Guía de testing
- 📊 [ESTADO_ACTUAL_PROYECTO.md](ESTADO_ACTUAL_PROYECTO.md) - Estado actual y roadmap
- 💡 [CONTRIBUTING.md](CONTRIBUTING.md) - Guía de contribución

### 2. Familiarizarte con la Estructura

```
Senselab_Core_API/
├── app/
│   ├── Http/Controllers/API/  ← 44 controladores de API
│   ├── Models/                ← 59 modelos Eloquent
│   ├── Http/Requests/         ← Validaciones (FormRequests)
│   └── Http/Resources/        ← Transformadores de datos
├── database/
│   ├── migrations/            ← 66 migraciones
│   └── seeders/               ← 9 seeders (datos maestros)
├── routes/
│   └── api.php                ← 413 rutas API
└── tests/
    ├── Feature/               ← Tests de integración
    └── Unit/                  ← Tests unitarios
```

### 3. Crear tus Propios Datos de Prueba

```bash
# Crear una nueva empresa
POST /api/empresas
{
    "nombre": "Mi Empresa de Prueba",
    "cedula_juridica": "3-101-999999",
    "regimen_tributario_id": 1,
    "email": "miempresa@example.com"
}

# Crear un nuevo usuario
POST /api/usuarios
{
    "nombre": "Juan Pérez",
    "email": "juan@example.com",
    "password": "password123",
    "empresa_id": 1
}
```

### 4. Ejecutar Tests y Contribuir

```bash
# Antes de hacer commit, asegúrate que los tests pasen
php artisan test

# Crear una nueva rama para tu feature
git checkout -b feature/mi-nueva-funcionalidad

# Hacer tus cambios y commit
git add .
git commit -m "Descripción de cambios"

# Push y crear Pull Request
git push origin feature/mi-nueva-funcionalidad
```

---

## 🆘 Soporte

Si tienes problemas que no están cubiertos en esta guía:

1. **Revisar Issues en GitHub:** https://github.com/SenseLab-dev/Senselab_Core_API/issues
2. **Crear un nuevo Issue** con:
   - Descripción del problema
   - Pasos para reproducir
   - Versiones de PHP, MySQL, Composer
   - Logs de error (si aplica)

3. **Contactar al equipo:**
   - 📧 Email: soporte@senselab.com
   - 💬 WhatsApp: +(506)8973-5665
   - 🌐 Web: https://senselab.com

---

## ✅ Checklist de Instalación

Marca cada paso cuando lo completes:

- [ ] PHP 8.2+ instalado y verificado
- [ ] Composer instalado y verificado
- [ ] MySQL instalado y funcionando
- [ ] Repositorio clonado
- [ ] Dependencias instaladas (`composer install`)
- [ ] Archivo `.env` configurado
- [ ] Base de datos `api_db` creada
- [ ] Base de datos `api_db_testing` creada
- [ ] Migraciones ejecutadas (`php artisan migrate`)
- [ ] Seeders ejecutados (`php artisan db:seed`)
- [ ] Servidor iniciado (`php artisan serve`)
- [ ] Login probado y funcionando
- [ ] Token obtenido correctamente
- [ ] Swagger accesible en `/api/documentation`
- [ ] Tests ejecutados (`php artisan test`)
- [ ] Documentación revisada

**¡Felicidades! Ya tienes Senselab Core API funcionando en tu entorno local.** 🎉

---

**Desarrollado con ❤️ por Senselab**  
**Build with Sense**
