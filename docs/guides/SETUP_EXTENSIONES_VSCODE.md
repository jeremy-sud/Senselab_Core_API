# 🛠️ Guía de Setup - Extensiones VS Code y Herramientas de Desarrollo

## ✅ Extensiones Instaladas

VS Code recomendará estas 10 extensiones automáticamente al abrir el proyecto:

### **Tier 1 - CRÍTICAS (Instala PRIMERO)**

#### 1. **REST Client** - `humao.rest-client`
```
Prueba endpoints directamente en VS Code sin Postman
```
**Cómo usar:**
- Abre `examples.http` en la raíz del proyecto
- VS Code mostrará botones "Send Request" sobre cada endpoint
- Los resultados aparecen en un panel lateral
- Variables definidas en `@baseUrl` y `@token`

**Ejemplo:**
```http
GET http://localhost:8000/api/v1/hacienda/comprobantes
Authorization: Bearer YOUR_TOKEN
```

---

#### 2. **Swagger Viewer** - `arjun.swagger-viewer`
```
Visualiza documentación OpenAPI/Swagger
```
**Cómo usar:**
- Abre archivo `docs/swagger.json` o `docs/openapi.yaml`
- Renderiza especificación DGT-R-000-2024 automáticamente
- Navega por endpoints interactivamente
- Ver schemas de validación

**Comandos:**
- Ctrl+K → "Swagger Viewer: Preview" en el archivo

---

#### 3. **Thunder Client** - `rangav.vscode-thunder-client`
```
Cliente HTTP avanzado (alternativa a Postman)
```
**Cómo usar:**
- Click en el ícono de rayo en la barra lateral de VS Code
- Crear colecciones de requests
- Guardar automáticamente junto al código
- Historial, variables de ambiente, testing

**Ventajas sobre REST Client:**
- UI más completa
- Mejor para testing de seguridad
- Variables de ambiente visuales
- Genera reportes

---

#### 4. **GitLens** - `eamodio.gitlens`
```
Ver historial y auditoría de cambios en el código
```
**Cómo usar:**
- Hover sobre una línea → ver quién y cuándo cambió
- Click en el ícono de GitLens en la barra lateral
- Explorar commits, ramas, inspeccionar cambios
- Ver línea de blame para auditoría

**Útil para:**
- Rastrear cambios en rate limiting
- Revisar cambios de encriptación
- Auditoría de modificaciones (GDPR/LGPD)

---

#### 5. **Better Comments** - `aaron-bond.better-comments`
```
Destaca comentarios especiales con colores
```
**Cómo usar:**
- Tipos de comentarios configurados:
  - `//!` → 🔴 Crítico (rojo)
  - `//?` → 🔵 Pregunta (azul)
  - `//TODO` → 🟠 To-do (naranja)
  - `//FIXME` → 🔴 Fix necesario (rojo)
  - `//RATE_LIMITING` → 💖 Rate Limiting (rosa)
  - `//ENCRYPTION` → 💜 Encryption (púrpura)
  - `//AUDIT` → 💚 Audit (verde)
  - `//HACIENDA` → 🟡 Hacienda (amarillo)

**Ejemplo:**
```php
//! CRITICAL - Changes to encryption keys require vault rotation
// ENCRYPTION - This field uses AES-256-CBC transparent encryption
// RATE_LIMITING - API endpoint limited to 60 requests/minute
// AUDIT - This action is tracked in audit_logs table
// HACIENDA - DGT-R-000-2024 v4.4 compliant
```

---

### **Tier 2 - RECOMENDADAS (Instala después)**

#### 6. **Intelephense** - `intelephense.intelephense`
```
Intellisense PHP mejorado (autocomplete avanzado)
```

---

#### 7. **XDebug (PHP Debug)** - `xdebug.php-debug`
```
Debuggear aplicaciones PHP con breakpoints
```
**Ya configurado en:** `.vscode/launch.json`

---

#### 8. **PHPStan** - `phpstan.phpstan`
```
Análisis estático nivel 8
```
**Ya configurado en:** `phpstan.neon`

---

#### 9. **PHP CS Fixer** - `junstyle.php-cs-fixer`
```
FIX automático de formato PSR-12
```
**Ya configurado:** `Ctrl+S` ejecuta automáticamente

---

#### 10. **GitHub Copilot** - `GitHub.copilot`
```
Asistencia IA (requiere licencia)
```

---

## 🎯 Workflows Recomendados

### **Workflow 1: Testing de API Hacienda**

1. Abre `examples.http`
2. Completa `@token` con tu bearer token
3. Presiona "Send Request" en el endpoint deseado
4. Revisa respuesta en panel lateral

### **Workflow 2: Debugging Rate Limiting**

1. Abre archivo con limiter (ej: `RateLimitingService.php`)
2. Click en número de línea para breakpoint
3. Presiona F5 o click en "Run and Debug"
4. Selecciona "Listen for XDebug (Docker)"
5. Ejecuta request en REST Client
6. Depura paso a paso

### **Workflow 3: Revisar Auditoría de Cambios**

1. Abre el archivo que quieres auditar
2. Click en ícono GitLens → "Open File Blame"
3. Hover sobre líneas para ver quién/cuándo
4. Click en commit → ver cambios completos

### **Workflow 4: Documentar con Better Comments**

```php
// Usa los tags especiales para mejor claridad
// ENCRYPTION - Este campo está encriptado automáticamente
// RATE_LIMITING - Limitado a 5 requests/hora
// AUDIT - Cambio registrado en logs
// HACIENDA - Cumple DGT-R-000-2024
// FIXME - Necesita refactor después de Q2
// TODO - Implementar soporte XAdES-EPES completo
// ? - ¿Por qué usamos Mod-9 aquí?
// ! - CRITICAL - No modificar sin supervisión
```

---

## 🔧 Configuración de Ambiente (variables REST Client)

Archivo: `examples.http` - Líneas 1-5

```http
@baseUrl = http://localhost:8000/api/v1
@token = your_bearer_token_here
@contentType = application/json
```

**Cómo obtener token:**

```bash
# Login y obtener token JWT
curl -X POST http://localhost:8000/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"user@example.com","password":"password"}'

# Copiar el token en la variable @token de examples.http
```

---

## 🚀 Comandos Rápidos

### **VS Code Shortcuts**

| Función | Shortcut |
|---------|----------|
| REST Client: Send Request | Ctrl+Alt+R |
| Abrir Debug | F5 |
| Agregar Breakpoint | Click en línea |
| Toggle Breakpoint | F9 |
| Continue (Debug) | F5 |
| Step Over | F10 |
| Step Into | F11 |
| GitLens: Blame | Shift+Alt+B |
| Format Document | Shift+Alt+F |
| Quick Fix | Ctrl+. |

---

## 🐛 Debugging con XDebug - Guía Paso a Paso

### **1. Verificar que Docker está corriendo:**
```bash
docker-compose ps
# Debería haber contenedor 'php' ejecutándose
```

### **2. Abrir archivo a depurar:**
```php
// app/Services/RateLimitingService.php
class RateLimitingService {
    public function checkLimit() { // ← Breakpoint aquí
        // ...
    }
}
```

### **3. Agregar breakpoint:**
- Click en número de línea (izquierda)
- Punto rojo indica breakpoint activo

### **4. Iniciar debugging:**
- Presiona F5
- Selecciona "Listen for XDebug (Docker)"
- Panel de Debug abre en la izquierda

### **5. Ejecutar request:**
- En `examples.http` presiona "Send Request"
- Ejecución se pausa en el breakpoint
- Variables visibles en panel Debug

### **6. Navegar debugging:**
- F5: Continuar hasta siguiente breakpoint
- F10: Step over (siguiente línea)
- F11: Step into (entrar en función)
- Shift+F11: Step out

---

## 📊 PHPStan - Análisis Estático Nivel 8

### **Ejecutar análisis:**
```bash
./vendor/bin/phpstan analyze app --level=8
```

### **Auto-fix con PHP CS Fixer:**
```bash
./vendor/bin/php-cs-fixer fix app
```

### **En VS Code:**
- Abre cualquier archivo PHP
- Verá warnings/errors de PHPStan
- Problemas → panel en VS Code mostrará errores

---

## 📝 Archivos de Configuración

```
.vscode/
├── extensions.json          # Lista de extensiones recomendadas
├── settings.json            # Configuración de extensiones
├── launch.json              # Configuración de XDebug
examples.http               # Ejemplos de REST requests
docker/php/
├── php.ini                  # Configuración PHP
└── xdebug.ini              # Configuración XDebug
```

---

## ✅ Checklist - Setup Inicial

- [ ] Instalar extensiones VS Code (verá notificación)
- [ ] Abrir `examples.http` para verificar REST Client funciona
- [ ] Abrir Docker Desktop / ejecutar `docker-compose up -d`
- [ ] Ejecutar tests: `php artisan test`
- [ ] Obtener JWT token
- [ ] Probar endpoint de Hacienda con token en `examples.http`
- [ ] Agregar breakpoint y ejecutar con XDebug (F5)

---

## 🆘 Troubleshooting

### **REST Client no muestra "Send Request"**
- Guardar archivo como `.http` o `.rest`
- Reload VS Code (Ctrl+Shift+P → Reload Window)

### **XDebug no conecta**
```bash
# Verificar que container tiene XDebug
docker exec ursol-php php -m | grep xdebug

# Si no aparece, rebuild:
docker-compose build --no-cache php
docker-compose up -d
```

### **PHPStan muestra demasiados errores**
- Revisar `phpstan.neon` para exclusiones
- Usar `@phpstan-ignore-line` para ignorar línea específica

### **Extensiones no auto-cargan**
```bash
# Forzar recarga
Ctrl+Shift+P → "Extensions: Show Recommended Extensions"
```

---

## 📚 Recursos Adicionales

- **REST Client**: https://marketplace.visualstudio.com/items?itemName=humao.rest-client
- **Swagger Viewer**: https://marketplace.visualstudio.com/items?itemName=Arjun.swagger-viewer
- **Thunder Client**: https://www.thunderclient.com/
- **GitLens**: https://www.gitkraken.com/gitlens
- **XDebug**: https://xdebug.org/
- **PHPStan**: https://phpstan.org/

---

## 🎓 Próximos Pasos

1. ✅ Setup completado
2. 📖 Leer esta guía
3. 🔧 Instalar extensiones
4. 🧪 Probar REST Client con `examples.http`
5. 🐛 Practicar debugging con XDebug
6. 📊 Revisar análisis PHPStan
7. 🚀 Comenzar desarrollo

---

**Última actualización:** 2026-02-08  
**Versión del API:** 2.0.0 (FASE 1 & FASE 2.1)

