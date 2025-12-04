# 🧪 Cómo Probar la API - Guía Completa

**Sistemas Ursol S.A.**  
**Actualizado:** 21 de noviembre de 2025

---

## 📋 Índice

1. [Opción 1: Swagger UI (Más Fácil)](#opción-1-swagger-ui-más-fácil)
2. [Opción 2: Postman (Más Profesional)](#opción-2-postman-más-profesional)
3. [Opción 3: cURL (Terminal)](#opción-3-curl-terminal)
4. [Opción 4: Thunder Client (VS Code)](#opción-4-thunder-client-vs-code)
5. [Opción 5: Insomnia](#opción-5-insomnia)
6. [Probar en Producción/Deploy](#probar-en-producciódeploy)

---

## 🚀 Iniciar el Servidor Localmente

Antes de probar, inicia el servidor de desarrollo:

```bash
cd /home/dawnweaber/Workspace/Ursol-CAST-API
php artisan serve
```

Verás:
```
INFO  Server running on [http://127.0.0.1:8000].
```

**La API está corriendo en:** `http://localhost:8000`

---

## Opción 1: Swagger UI (Más Fácil) ⭐ RECOMENDADA

### ✅ Ventajas:
- No necesitas instalar nada
- Interfaz visual e interactiva
- Documentación incluida
- Autenticación integrada
- Ver ejemplos de respuestas

### 🎯 Cómo usarlo:

#### Paso 1: Abrir Swagger
Abre tu navegador en:
```
http://localhost:8000/api/documentation
```

#### Paso 2: Hacer Login para Obtener Token

1. Busca el endpoint: `POST /api/auth/login`
2. Click en "Try it out"
3. Edita el JSON:
   ```json
   {
     "email": "admin@ursol.com",
     "password": "admin123"
   }
   ```
4. Click en "Execute"
5. En la respuesta, copia el **token**:
   ```json
   {
     "token": "1|aBcDeFgHiJkLmNoPqRsTuVwXyZ..."
   }
   ```

#### Paso 3: Autorizar en Swagger

1. Click en el botón **"Authorize"** (candado verde arriba a la derecha)
2. En el campo "Value", escribe:
   ```
   Bearer TU_TOKEN_AQUI
   ```
   Ejemplo:
   ```
   Bearer 1|aBcDeFgHiJkLmNoPqRsTuVwXyZ...
   ```
3. Click en "Authorize"
4. Click en "Close"

#### Paso 4: Probar Endpoints Protegidos

Ahora puedes probar cualquier endpoint:

**Ejemplo: Listar Productos**
1. Busca: `GET /api/productos`
2. Click en "Try it out"
3. Click en "Execute"
4. Ve la respuesta en "Response body"

**Ejemplo: Crear Producto**
1. Busca: `POST /api/productos`
2. Click en "Try it out"
3. Edita el JSON:
   ```json
   {
     "nombre": "Laptop HP",
     "codigo": "LAP-001",
     "precio": 500000,
     "categoria_id": 1,
     "unidad_medida_id": 1,
     "tipo": "producto"
   }
   ```
4. Click en "Execute"
5. Ve la respuesta

### 📸 Captura de Pantalla del Flujo:

```
┌─────────────────────────────────────────────────┐
│  Swagger UI - http://localhost:8000/api/docs   │
├─────────────────────────────────────────────────┤
│  🔓 Authorize                                   │
│                                                  │
│  Auth                                            │
│  ▼ POST /api/auth/login       Try it out       │
│  ▼ POST /api/auth/logout                       │
│  ▼ GET  /api/auth/user                         │
│                                                  │
│  Empresas                                        │
│  ▼ GET    /api/empresas       Try it out       │
│  ▼ POST   /api/empresas                        │
│  ▼ GET    /api/empresas/{id}                   │
│  ▼ PUT    /api/empresas/{id}                   │
│  ▼ DELETE /api/empresas/{id}                   │
│                                                  │
│  Productos                                       │
│  ▼ GET    /api/productos      Try it out       │
│  ...                                             │
└─────────────────────────────────────────────────┘
```

---

## Opción 2: Postman (Más Profesional) ⭐ RECOMENDADA

### ✅ Ventajas:
- Herramienta profesional
- Guardar colecciones de requests
- Variables de entorno
- Tests automatizados
- Compartir con equipo

### 🎯 Cómo usarlo:

#### Paso 1: Instalar Postman
Descarga e instala desde: https://www.postman.com/downloads/

#### Paso 2: Crear Nueva Collection

1. Abre Postman
2. Click en "New" → "Collection"
3. Nombre: "Ursol CAST API"
4. Guarda

#### Paso 3: Configurar Variable de Entorno

1. Click en "Environments" (izquierda)
2. Click en "+" para nuevo ambiente
3. Nombre: "Local"
4. Agregar variables:
   ```
   Variable:  base_url
   Initial:   http://localhost:8000/api
   Current:   http://localhost:8000/api
   
   Variable:  token
   Initial:   (dejar vacío)
   Current:   (dejar vacío)
   ```
5. Guardar

#### Paso 4: Crear Request de Login

1. En tu collection, "Add request"
2. Nombre: "Login"
3. Método: **POST**
4. URL: `{{base_url}}/auth/login`
5. Tab "Body" → "raw" → "JSON"
6. Contenido:
   ```json
   {
     "email": "admin@ursol.com",
     "password": "admin123"
   }
   ```
7. Click en "Send"
8. En la respuesta, copia el token
9. Pégalo en la variable de entorno `token`

#### Paso 5: Crear Request de Listar Productos

1. "Add request"
2. Nombre: "Listar Productos"
3. Método: **GET**
4. URL: `{{base_url}}/productos`
5. Tab "Authorization":
   - Type: "Bearer Token"
   - Token: `{{token}}`
6. Click en "Send"

#### Paso 6: Crear Request de Crear Producto

1. "Add request"
2. Nombre: "Crear Producto"
3. Método: **POST**
4. URL: `{{base_url}}/productos`
5. Tab "Authorization": Bearer Token → `{{token}}`
6. Tab "Body" → "raw" → "JSON":
   ```json
   {
     "nombre": "Mouse Logitech",
     "codigo": "MOU-001",
     "descripcion": "Mouse inalámbrico",
     "precio": 15000,
     "categoria_id": 1,
     "unidad_medida_id": 1,
     "tipo": "producto",
     "activo": true
   }
   ```
7. Click en "Send"

### 💡 Tips de Postman:

**Automatizar token:**
En el request de Login, tab "Tests", agrega:
```javascript
var jsonData = pm.response.json();
pm.environment.set("token", jsonData.token);
```

Ahora cada vez que hagas login, el token se actualiza automáticamente.

---

## Opción 3: cURL (Terminal)

### ✅ Ventajas:
- No requiere instalación extra
- Rápido para pruebas simples
- Scripteable
- Incluido en Linux/Mac

### 🎯 Comandos de Ejemplo:

#### Login:
```bash
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "admin@ursol.com",
    "password": "admin123"
  }'
```

**Respuesta:**
```json
{
  "message": "Login exitoso",
  "token": "1|aBcDeFgHiJkLmNoPqRsTuVwXyZ...",
  "usuario": {...},
  "permisos": [...]
}
```

#### Guardar token en variable:
```bash
TOKEN=$(curl -s -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@ursol.com","password":"admin123"}' \
  | jq -r '.token')

echo $TOKEN
```

#### Listar Productos:
```bash
curl -X GET http://localhost:8000/api/productos \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json"
```

#### Crear Producto:
```bash
curl -X POST http://localhost:8000/api/productos \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "nombre": "Teclado Mecánico",
    "codigo": "TEC-001",
    "precio": 45000,
    "categoria_id": 1,
    "unidad_medida_id": 1,
    "tipo": "producto"
  }'
```

#### Ver Producto Específico:
```bash
curl -X GET http://localhost:8000/api/productos/1 \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json"
```

#### Actualizar Producto:
```bash
curl -X PUT http://localhost:8000/api/productos/1 \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "nombre": "Teclado Mecánico RGB",
    "precio": 55000
  }'
```

#### Eliminar Producto:
```bash
curl -X DELETE http://localhost:8000/api/productos/1 \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json"
```

---

## Opción 4: Thunder Client (VS Code)

### ✅ Ventajas:
- Integrado en VS Code
- No necesitas cambiar de aplicación
- Ligero y rápido
- Gratis

### 🎯 Cómo usarlo:

#### Paso 1: Instalar Extensión
1. En VS Code: Ctrl+Shift+X
2. Buscar "Thunder Client"
3. Instalar

#### Paso 2: Abrir Thunder Client
1. Click en el ícono del rayo (⚡) en la barra lateral
2. O Ctrl+Shift+P → "Thunder Client: New Request"

#### Paso 3: Crear Request
1. Click en "New Request"
2. Método: POST
3. URL: `http://localhost:8000/api/auth/login`
4. Tab "Body" → JSON:
   ```json
   {
     "email": "admin@ursol.com",
     "password": "admin123"
   }
   ```
5. Click "Send"
6. Copia el token de la respuesta

#### Paso 4: Usar el Token
1. Nuevo request
2. URL: `http://localhost:8000/api/productos`
3. Tab "Auth" → "Bearer"
4. Pega tu token
5. Send

---

## Opción 5: Insomnia

### ✅ Ventajas:
- Similar a Postman
- Interfaz limpia
- GraphQL support
- Gratis

### 🎯 Cómo usarlo:

1. Descargar: https://insomnia.rest/download
2. Crear "New Request Collection"
3. Agregar requests igual que en Postman
4. Usar variables de entorno

---

## 🌐 Probar en Producción/Deploy

### Si ya desplegaste la API en un servidor:

#### Opción A: En Servidor con Dominio
```bash
# Cambiar base_url a tu dominio
https://api.ursol.com/api/auth/login
```

#### Opción B: En VPS con IP Pública
```bash
# Cambiar a la IP de tu servidor
http://123.45.67.89:8000/api/auth/login
```

#### Opción C: Laravel Vapor / AWS
```bash
# URL generada por Vapor
https://tu-app.vapor-farm-XX.com/api/auth/login
```

---

## 🔍 Verificar Estado de la API

### Ver Todas las Rutas:
```bash
php artisan route:list --path=api
```

### Ver Rutas de un Controlador:
```bash
php artisan route:list --path=api/productos
```

### Ver Estadísticas:
```bash
php artisan route:list --path=api | wc -l
# Debe mostrar: 413 rutas
```

---

## 📊 Endpoints Disponibles (413 total)

### Autenticación (3 endpoints)
```
POST   /api/auth/login       - Login
POST   /api/auth/logout      - Logout
GET    /api/auth/user        - Usuario autenticado
```

### Empresas (5 endpoints)
```
GET    /api/empresas         - Listar
POST   /api/empresas         - Crear
GET    /api/empresas/{id}    - Ver
PUT    /api/empresas/{id}    - Actualizar
DELETE /api/empresas/{id}    - Eliminar
```

### Productos (5 endpoints)
```
GET    /api/productos        - Listar (con paginación)
POST   /api/productos        - Crear
GET    /api/productos/{id}   - Ver
PUT    /api/productos/{id}   - Actualizar
DELETE /api/productos/{id}   - Eliminar (soft delete)
```

### Clientes, Proveedores, Ventas, etc.
Ver documentación completa en: [API_DOCUMENTATION.md](API_DOCUMENTATION.md)

---

## 🧪 Tests Automatizados

### Ejecutar todos los tests:
```bash
php artisan test
```

### Ejecutar tests específicos:
```bash
# Solo tests de autenticación
php artisan test --filter=AuthTest

# Solo tests de productos
php artisan test --filter=ProductoTest

# Test específico
php artisan test --filter=test_puede_hacer_login
```

### Ver cobertura:
```bash
php artisan test --coverage
```

---

## 📝 Ejemplos Completos de Flujos

### Flujo 1: Crear Venta Completa

```bash
# 1. Login
TOKEN=$(curl -s -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@ursol.com","password":"admin123"}' \
  | jq -r '.token')

# 2. Crear cliente
curl -X POST http://localhost:8000/api/clientes \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "nombre": "Juan Pérez",
    "identificacion": "1-0234-0567",
    "email": "juan@example.com"
  }'

# 3. Crear producto
curl -X POST http://localhost:8000/api/productos \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "nombre": "Laptop Dell",
    "codigo": "LAP-001",
    "precio": 500000,
    "categoria_id": 1,
    "unidad_medida_id": 1,
    "tipo": "producto"
  }'

# 4. Crear venta
curl -X POST http://localhost:8000/api/ventas \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "cliente_id": 1,
    "productos": [
      {
        "producto_id": 1,
        "cantidad": 2,
        "precio_unitario": 500000
      }
    ]
  }'
```

### Flujo 2: Gestión de Inventario

```bash
# 1. Ver stock actual
curl -X GET http://localhost:8000/api/inventario/producto/1 \
  -H "Authorization: Bearer $TOKEN"

# 2. Entrada de inventario
curl -X POST http://localhost:8000/api/inventario/entradas \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "producto_id": 1,
    "cantidad": 50,
    "almacen_id": 1,
    "motivo": "Compra"
  }'

# 3. Salida de inventario
curl -X POST http://localhost:8000/api/inventario/salidas \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "producto_id": 1,
    "cantidad": 5,
    "almacen_id": 1,
    "motivo": "Venta"
  }'

# 4. Ver kardex
curl -X GET http://localhost:8000/api/inventario/kardex/1 \
  -H "Authorization: Bearer $TOKEN"
```

---

## 🔧 Troubleshooting

### Error 401 Unauthorized
**Problema:** "Unauthenticated"  
**Solución:** 
- Verifica que el token sea correcto
- Asegúrate de incluir "Bearer " antes del token
- El token expira, haz login nuevamente

### Error 404 Not Found
**Problema:** "Route not found"  
**Solución:**
- Verifica que el servidor esté corriendo
- Revisa que la URL sea correcta
- Ejecuta: `php artisan route:list` para ver rutas disponibles

### Error 500 Internal Server Error
**Problema:** Error del servidor  
**Solución:**
- Revisa los logs: `storage/logs/laravel.log`
- Verifica la configuración de .env
- Ejecuta: `php artisan config:clear`

### Error 419 CSRF Token Mismatch
**Problema:** Solo si usas web routes  
**Solución:** 
- Usa `/api/*` routes (no requieren CSRF)
- O incluye el header: `X-CSRF-TOKEN`

---

## 📚 Recursos Adicionales

- **Swagger UI:** http://localhost:8000/api/documentation
- **Documentación Completa:** [API_DOCUMENTATION.md](API_DOCUMENTATION.md)
- **Guía de Testing:** [TESTING_GUIDE.md](TESTING_GUIDE.md)
- **Postman Collections:** (próximamente exportaremos una)

---

## 🎯 Resumen Rápido

### Para desarrollo diario:
1. **Swagger UI** - Pruebas rápidas y visuales
2. **Thunder Client** - Sin salir de VS Code
3. **Postman** - Desarrollo profesional

### Para scripts/automatización:
4. **cURL** - Desde terminal

### Para compartir con frontend:
5. **Swagger UI** - Compartir URL de documentación

---

**¡Ya puedes probar tu API de todas las formas posibles!** 🚀

**Desarrollado por Sistemas Ursol S.A.**
