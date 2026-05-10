# Configuración de Base de Datos - Senselab Core API

## 🗄️ Base de Datos MySQL

Este proyecto utiliza **MySQL** como motor de base de datos principal. **NO se utiliza SQLite**.

---

## 📍 Ubicación de las Bases de Datos

### Base de Datos de Producción/Desarrollo
- **URL:** http://localhost/phpmyadmin/index.php?route=/database/structure&db=api_db
- **Nombre:** `api_db`
- **Servidor:** localhost (127.0.0.1)
- **Puerto:** 3306
- **Usuario:** root
- **Acceso:** phpMyAdmin

### Base de Datos de Testing
- **URL:** http://localhost/phpmyadmin/index.php?route=/database/structure&db=api_db_testing
- **Nombre:** `api_db_testing`
- **Servidor:** localhost (127.0.0.1)
- **Puerto:** 3306
- **Usuario:** root
- **Acceso:** phpMyAdmin

---

## ⚙️ Configuración del Proyecto

### Archivo .env (Desarrollo)
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=api_db
DB_USERNAME=senselab_user
DB_PASSWORD=senselab_password
```

### Archivo .env.testing (Testing) - Crear si no existe
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=api_db_testing
DB_USERNAME=senselab_user
DB_PASSWORD=senselab_password
```

### PHPUnit (tests/phpunit.xml)
```xml
<env name="DB_CONNECTION" value="mysql"/>
<env name="DB_DATABASE" value="api_db_testing"/>
```

---

## 📋 Archivos de Configuración Actualizados

### ✅ config/database.php
- Conexión por defecto: `mysql` (no sqlite)
- Configuración MySQL completa
- Charset: `utf8mb4`
- Collation: `utf8mb4_unicode_ci`

### ✅ config/queue.php
- Batching database: `mysql` (no sqlite)
- Failed jobs database: `mysql` (no sqlite)

### ✅ .env.example
- DB_CONNECTION: `mysql` (no sqlite)
- Variables de MySQL descomentadas

### ✅ composer.json
- Script de SQLite eliminado
- No se crea archivo `database.sqlite`

### ✅ database/.gitignore
- Referencias a `*.sqlite*` eliminadas

---

## 🗂️ Archivo de Referencia SQL

### api_db.sql
- **Ubicación:** `/home/dawnweaber/Workspace/Senselab_Core_API/api_db.sql`
- **Propósito:** Solo referencia para desarrollo
- **NO es la base de datos activa**
- La base de datos real está en MySQL (localhost/phpmyadmin)

---

## 🔧 Comandos Útiles

### Crear/Actualizar Tablas
```bash
# Ejecutar migraciones en desarrollo
php artisan migrate

# Ejecutar migraciones en testing
php artisan migrate --env=testing

# Refrescar base de datos (CUIDADO: borra datos)
php artisan migrate:fresh

# Refrescar con seeders
php artisan migrate:fresh --seed
```

### Verificar Conexión
```bash
# Verificar conexión a MySQL
php artisan tinker
>>> DB::connection()->getPdo();

# Ver nombre de base de datos activa
>>> DB::connection()->getDatabaseName();
```

### Ejecutar Tests
```bash
# Todos los tests (usa api_db_testing)
php artisan test

# Tests específicos
php artisan test --filter=AuthTest

# Con cobertura
php artisan test --coverage
```

---

## 🚨 Notas Importantes

1. **NO usar SQLite:** El proyecto está configurado para MySQL únicamente
2. **Archivo api_db.sql:** Es solo referencia, no se importa automáticamente
3. **phpMyAdmin:** Es la herramienta recomendada para administrar las bases de datos
4. **Testing:** Usa base de datos separada (`api_db_testing`) para no afectar datos de desarrollo
5. **Migraciones:** Laravel controla la estructura de tablas, no se edita SQL directamente en producción

---

## 🔐 Seguridad

### Producción ##esta informacion aún no se ha compartido, pero es importante destacar que en producción se deben usar credenciales seguras y no el usuario root. además de configurar el acceso remoto a la base de datos solo para IPs autorizadas. ya cuando se tenga la información de producción, se actualizará esta sección con los detalles específicos.
En un entorno de producción, configurar:
```env
DB_HOST=<servidor-mysql-remoto>
DB_DATABASE=<nombre-bd-produccion>
DB_USERNAME=<usuario-no-root>
DB_PASSWORD=<contraseña-segura>
```

### Respaldos
- Configurar backups automáticos de MySQL
- Usar `mysqldump` para exportar datos
- No versionar archivos .env con credenciales reales

---

## 📊 Estructura de Base de Datos

Ver archivo `api_db.sql` para la estructura completa de 59 tablas:
- Empresas y Sucursales
- Productos e Inventario
- Ventas y Compras
- Contabilidad
- Facturación Electrónica (DGT)
- Recursos Humanos
- Transporte
- RBAC (Roles y Permisos)

---

**Última actualización:** 24 de Marzo de 2026  
**Motor:** MySQL 8.0+  
**Charset:** utf8mb4  
**Collation:** utf8mb4_unicode_ci
