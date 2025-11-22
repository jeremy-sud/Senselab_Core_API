# 📊 Cambios en Base de Datos - URL Shortener y Código de Barras

**Fecha**: 22 de Noviembre, 2025  
**Commit**: bfc9ac6  
**Autor**: Jeremy Arias Solano

## 🎯 Resumen de Cambios

Se implementaron dos mejoras importantes en la base de datos:

1. **Nueva tabla `url_shorter_db`** - Sistema completo de acortador de URLs
2. **Nueva columna `codigo_barras`** en tabla `productos` - Identificación por código de barras

---

## 📋 Tabla: url_shorter_db

### Estructura de la Tabla

```sql
CREATE TABLE url_shorter_db (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  empresa_id INT UNSIGNED NOT NULL,
  usuario_id INT UNSIGNED NULL,
  url_original TEXT NOT NULL,
  url_corta VARCHAR(100) NOT NULL UNIQUE,
  slug VARCHAR(50) NOT NULL UNIQUE,
  clicks INT UNSIGNED DEFAULT 0,
  descripcion VARCHAR(255) NULL,
  expira_en TIMESTAMP NULL,
  activo TINYINT(1) NOT NULL DEFAULT 1,
  eliminado TINYINT(1) NOT NULL DEFAULT 0,
  creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  actualizado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Índices

| Nombre | Tipo | Columnas | Propósito |
|--------|------|----------|-----------|
| PRIMARY | PRIMARY KEY | id | Clave primaria |
| url_corta | UNIQUE | url_corta | Evitar URLs cortas duplicadas |
| slug | UNIQUE | slug | Evitar slugs duplicados |
| idx_empresa | INDEX | empresa_id | Filtrado por empresa |
| idx_usuario | INDEX | usuario_id | Filtrado por usuario |
| idx_slug | INDEX | slug | Búsqueda rápida por slug |
| idx_activo | INDEX | activo | Filtrado por estado activo |
| idx_eliminado | INDEX | eliminado | Filtrado por soft-delete |

### Foreign Keys

| Constraint | Columna Local | Tabla Referenciada | Columna Referenciada | On Delete |
|------------|---------------|-------------------|---------------------|-----------|
| fk_url_shorter_empresa | empresa_id | empresas | id | CASCADE |
| fk_url_shorter_usuario | usuario_id | usuarios | id | SET NULL |

### Descripción de Campos

- **id**: Identificador único autoincrementable
- **empresa_id**: Empresa a la que pertenece la URL (obligatorio)
- **usuario_id**: Usuario que creó la URL (opcional)
- **url_original**: URL completa original a acortar (TEXT)
- **url_corta**: URL acortada generada (única, max 100 caracteres)
- **slug**: Código corto único para la URL (max 50 caracteres)
- **clicks**: Contador de veces que se ha usado la URL
- **descripcion**: Descripción opcional de la URL
- **expira_en**: Fecha de expiración opcional (NULL = nunca expira)
- **activo**: Estado activo/inactivo de la URL
- **eliminado**: Soft delete (0 = activo, 1 = eliminado)
- **creado_en**: Timestamp de creación
- **actualizado_en**: Timestamp de última actualización

---

## 📋 Tabla: productos (Modificación)

### Cambio Implementado

```sql
ALTER TABLE productos 
ADD COLUMN codigo_barras VARCHAR(100) NULL AFTER codigo,
ADD INDEX idx_codigo_barras (codigo_barras);
```

### Estructura Actualizada

| Campo | Tipo | Null | Default | Descripción |
|-------|------|------|---------|-------------|
| codigo | VARCHAR(100) | NO | NULL | Código interno del producto |
| **codigo_barras** | **VARCHAR(100)** | **YES** | **NULL** | **Código de barras (nuevo)** |
| nombre | VARCHAR(255) | NO | NULL | Nombre del producto |

### Índice Agregado

- **idx_codigo_barras**: Índice en columna `codigo_barras` para búsquedas eficientes

### Uso del Campo

- Permite almacenar códigos de barras estándar (EAN-13, UPC-A, Code 128, etc.)
- Máximo 100 caracteres
- Nullable (productos pueden no tener código de barras)
- Indexado para búsquedas rápidas

---

## 🔧 Migraciones Laravel

### 1. Migración: create_url_shorter_db_table

**Archivo**: `database/migrations/2025_11_22_142109_create_url_shorter_db_table.php`

```php
Schema::create('url_shorter_db', function (Blueprint $table) {
    $table->id();
    $table->unsignedInteger('empresa_id');
    $table->unsignedInteger('usuario_id')->nullable();
    $table->text('url_original');
    $table->string('url_corta', 100)->unique();
    $table->string('slug', 50)->unique();
    $table->unsignedInteger('clicks')->default(0);
    $table->string('descripcion', 255)->nullable();
    $table->timestamp('expira_en')->nullable();
    $table->boolean('activo')->default(true);
    $table->boolean('eliminado')->default(false);
    $table->timestamp('creado_en')->useCurrent();
    $table->timestamp('actualizado_en')->useCurrent()->useCurrentOnUpdate();
    
    // Índices y Foreign Keys
    $table->foreign('empresa_id')->references('id')->on('empresas')->onDelete('cascade');
    $table->foreign('usuario_id')->references('id')->on('usuarios')->onDelete('set null');
});
```

### 2. Migración: add_codigo_barras_to_productos_table

**Archivo**: `database/migrations/2025_11_22_142115_add_codigo_barras_to_productos_table.php`

```php
Schema::table('productos', function (Blueprint $table) {
    $table->string('codigo_barras', 100)->nullable()->after('codigo');
    $table->index('codigo_barras', 'idx_codigo_barras');
});
```

**Rollback**:
```php
Schema::table('productos', function (Blueprint $table) {
    $table->dropIndex('idx_codigo_barras');
    $table->dropColumn('codigo_barras');
});
```

---

## 🎨 Modelos Eloquent

### Modelo: UrlShortener

**Archivo**: `app/Models/UrlShortener.php`

#### Configuración

```php
protected $table = 'url_shorter_db';
const CREATED_AT = 'creado_en';
const UPDATED_AT = 'actualizado_en';
```

#### Fillable

```php
protected $fillable = [
    'empresa_id', 'usuario_id', 'url_original', 'url_corta', 
    'slug', 'clicks', 'descripcion', 'expira_en', 'activo', 'eliminado'
];
```

#### Relaciones

```php
public function empresa(): BelongsTo
public function usuario(): BelongsTo
```

#### Scopes

```php
public function scopeActivos($query)        // WHERE activo = 1
public function scopeNoEliminados($query)   // WHERE eliminado = 0
public function scopeNoExpirados($query)    // WHERE expira_en IS NULL OR expira_en > NOW()
```

#### Métodos Útiles

```php
public function incrementClicks(): void     // Incrementar contador
public function isExpired(): bool           // Verificar si expiró
public function isAvailable(): bool         // Verificar disponibilidad completa
```

### Modelo: Producto (Actualizado)

El campo `codigo_barras` ya estaba incluido en el `$fillable` del modelo:

```php
protected $fillable = [
    // ... otros campos
    'codigo',
    'codigo_barras',  // ✅ Ya existía
    'nombre',
    // ...
];
```

---

## 🌐 API Endpoints

### URL Shortener

#### Listar URLs
```http
GET /api/url-shortener
Query Params: ?empresa_id=1&activo=1&no_expirados=1&per_page=15
```

#### Crear URL
```http
POST /api/url-shortener
Body: {
  "empresa_id": 1,
  "usuario_id": 1,
  "url_original": "https://example.com/very/long/url",
  "slug": "custom-slug",  // Opcional, se genera automáticamente
  "descripcion": "Link promocional",
  "expira_en": "2025-12-31 23:59:59"
}
```

#### Obtener URL
```http
GET /api/url-shortener/{id}
```

#### Actualizar URL
```http
PUT /api/url-shortener/{id}
Body: {
  "descripcion": "Nueva descripción",
  "activo": false
}
```

#### Eliminar URL (Soft Delete)
```http
DELETE /api/url-shortener/{id}
```

#### Estadísticas
```http
GET /api/url-shortener/{id}/stats
Response: {
  "clicks": 42,
  "is_expired": false,
  "is_available": true,
  ...
}
```

#### Redirigir (Público)
```http
GET /s/{slug}
Redirige a la URL original y incrementa contador de clicks
```

### Productos (Búsqueda actualizada)

```http
GET /api/productos?search=7501234567890
```

Ahora la búsqueda incluye el campo `codigo_barras`:
- Busca en: `nombre`, `codigo`, **`codigo_barras`**, `descripcion`

---

## 📝 Validaciones

### StoreProductoRequest (Actualizado)

```php
'codigo_barras' => ['nullable', 'string', 'max:100']
```

### UrlShortener Validation

**Store**:
```php
'empresa_id' => 'required|exists:empresas,id',
'usuario_id' => 'nullable|exists:usuarios,id',
'url_original' => 'required|url|max:2000',
'slug' => 'nullable|string|max:50|unique:url_shorter_db,slug',
'descripcion' => 'nullable|string|max:255',
'expira_en' => 'nullable|date|after:now',
'activo' => 'boolean',
```

**Update**:
```php
'url_original' => 'sometimes|required|url|max:2000',
'slug' => 'sometimes|required|string|max:50|unique:url_shorter_db,slug,{id}',
'descripcion' => 'nullable|string|max:255',
'expira_en' => 'nullable|date',
'activo' => 'boolean',
```

---

## 🧪 Testing

### Verificar Tablas

```bash
docker-compose exec mysql mysql -uroot -proot_password -e "USE api_db; SHOW TABLES LIKE '%url_shorter%';"
```

### Verificar Estructura

```bash
docker-compose exec mysql mysql -uroot -proot_password -e "USE api_db; DESCRIBE url_shorter_db;"
```

### Verificar Foreign Keys

```bash
docker-compose exec mysql mysql -uroot -proot_password -e "
USE api_db; 
SELECT CONSTRAINT_NAME, TABLE_NAME, COLUMN_NAME, REFERENCED_TABLE_NAME 
FROM information_schema.KEY_COLUMN_USAGE 
WHERE TABLE_NAME = 'url_shorter_db' AND REFERENCED_TABLE_NAME IS NOT NULL;"
```

### Probar API

```bash
# Crear URL acortada
curl -X POST http://localhost:8000/api/url-shortener \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "empresa_id": 1,
    "url_original": "https://example.com/test",
    "descripcion": "Test URL"
  }'

# Listar URLs
curl -X GET http://localhost:8000/api/url-shortener \
  -H "Authorization: Bearer {token}"

# Redirigir (sin autenticación)
curl -L http://localhost:8000/s/{slug}
```

---

## 📊 Impacto en el Sistema

### Ventajas

✅ **URL Shortener**:
- Sistema completo de URLs acortadas multi-empresa
- Tracking de clicks
- Soporte para URLs temporales con expiración
- Soft delete para mantener historial
- Generación automática de slugs únicos

✅ **Código de Barras**:
- Búsqueda más eficiente de productos
- Compatibilidad con lectores de código de barras
- Indexado para rendimiento óptimo

### Consideraciones

⚠️ **Rendimiento**:
- Índices agregados pueden incrementar espacio en disco (~5-10%)
- Foreign keys garantizan integridad referencial

⚠️ **Migración de Datos**:
- `codigo_barras` es nullable, no requiere datos existentes
- `url_shorter_db` es tabla nueva, sin datos migrados

---

## 🔗 Archivos Relacionados

### Base de Datos
- Tabla: `url_shorter_db`
- Tabla modificada: `productos`

### Migraciones
- `database/migrations/2025_11_22_142109_create_url_shorter_db_table.php`
- `database/migrations/2025_11_22_142115_add_codigo_barras_to_productos_table.php`

### Modelos
- `app/Models/UrlShortener.php` (nuevo)
- `app/Models/Producto.php` (sin cambios, ya incluía codigo_barras)

### Controladores
- `app/Http/Controllers/API/UrlShortenerController.php` (nuevo)
- `app/Http/Controllers/API/ProductoController.php` (actualizado)

### Resources
- `app/Http/Resources/UrlShortenerResource.php` (nuevo)
- `app/Http/Resources/ProductoResource.php` (sin cambios)

### Requests
- `app/Http/Requests/StoreProductoRequest.php` (actualizado)
- `app/Http/Requests/UpdateProductoRequest.php` (ya incluía codigo_barras)

### Rutas
- `routes/api.php` (agregadas rutas de url-shortener)
- `routes/web.php` (agregada ruta pública /s/{slug})

---

**Última actualización**: 22 de Noviembre, 2025  
**Responsable**: Jeremy Arias Solano  
**Commit**: bfc9ac6
