# ⚙️ Guía Técnica de Integración — Laravel Core Backend API (`api.scisenselab.local`)
## Sistema Senselab Ecosistema Unificado (Solo Developer)

Este documento describe la arquitectura, la resolución de inquilinos (Tenants) y el flujo de autenticación del **Core Backend del Ecosistema Senselab** (`Senselab_Core_API`) basado en Laravel 12 y PHP 8.4.

---

## 🏛️ 1. Rol en el Ecosistema

La API Core centraliza la lógica empresarial transaccional de todo el ecosistema. Su rol técnico incluye:
1. **Multi-Tenant Aislado (PostgreSQL / MySQL):** Conmutar y aislar bases de datos de clientes corporativos en caliente mediante la cabecera `X-Senselab-Tenant-Id` o `X-Tenant-ID`.
2. **Servicios de Facturación Electrónica:** Recepción de comprobantes, firma criptográfica XAdES-EPES (.p12) y envío directo al Ministerio de Hacienda de Costa Rica.
3. **Control de Límites de Planes (SaaS):** Middleware de auditoría que bloquea escrituras operativas en caso de impago o exceso de límites de cuota (Usuarios, Facturas o Consultas de IA), devolviendo un código **`402 Payment Required`**.

```
                ┌──────────────────────────────────┐
                │        Senselab_Core_API         │
                └──────────────────────────────────┘
                                  ▲
       ┌──────────────────────────┼──────────────────────────┐
       │ (Header: Tenant ID)      │ (Bearer JWT Token)       │ (API Key)
       ▼                          ▼                          ▼
┌──────────────┐           ┌──────────────┐           ┌──────────────┐
│  Landing     │           │  ERP UI      │           │ Dev Portal   │
└──────────────┘           └──────────────┘           └──────────────┘
```

---

## 🏢 2. Middleware de Resolución Multi-Tenant

El backend detecta y conmuta la base de datos adecuada utilizando el identificador del inquilino enviado por el frontend. En local y producción, el middleware de Laravel intercepta y establece la conexión adecuada:

```php
// Ejemplo conceptual del Resolvedor de Tenants en Laravel
$tenantHeader = $request->header('X-Senselab-Tenant-Id') ?: $request->header('X-Tenant-ID');
$tenantId = $tenantHeader ?: 'sl_tenant_' . str_pad((string)$user->empresa_id, 6, '0', STR_PAD_LEFT);

// Conmutar base de datos dinámicamente
Tenancy::initialize($tenantId);
```

---

## 🔑 3. Verificación de Autenticación JWT

Las llamadas seguras hechas por el ERP y el Developer Portal inyectan el encabezado `Authorization: Bearer <token>`. El backend valida la firma criptográfica usando claves guardadas en el `.env` local. 

---

## 🚦 4. Desarrollo Local Unificado

Como único desarrollador, tu servidor Laravel se orquesta fácilmente para interactuar con tus frontends de forma local:

* **URL de Desarrollo Local (Caddy):** `https://api.scisenselab.local/api`
* **Puerto de Ejecución Local:** `http://localhost:8000` (Laravel local de PM2)

### Comandos de Control en el Workspace:
- **Iniciar todo el ecosistema:** `pm2 start /home/dawnweaber/Workspace/ecosystem.config.js`
- **Iniciar Proxy Caddy (HTTPS):** `sudo caddy start --config /home/dawnweaber/Workspace/Caddyfile`
- **Ver logs de la API:** `pm2 logs senselab-api`
- **Ejecutar migraciones en todos los tenants:** `php artisan tenants:migrate`
- **Ejecutar tests unitarios (PHPUnit):** `php artisan test`

---

## 📦 5. Sincronización de Contratos (TypeScript)

Cuando crees nuevos modelos, endpoints o recursos en el backend, recuerda documentar sus tipos de retorno en el archivo de tipos compartidos del frontend en:
`/home/dawnweaber/Workspace/senselab_erp_frontend/src/types/shared/user.ts`

Esto mantendrá alineados instantáneamente tanto al ERP como al Developer Portal a través de sus enlaces simbólicos.
