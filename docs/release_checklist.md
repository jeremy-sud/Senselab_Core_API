# Checklist Pre-Release — URSOL‑CAST API

**Última actualización:** 13 de febrero de 2026  
**Versión:** 2.1.0  
**Estado:** FASE 4 en progreso - Esperar a completar mejoras de calidad antes de producción  

🚨 **NOTA CRÍTICA:** Este proyecto está en FASE 4 (Calidad de Código). No se recomienda producción hasta completar:
- [ ] Reducir PHPStan errores (1974 → <30)
- [ ] Aumentar cobertura de tests (25% → >80%)
- [ ] Refactorizar 15 controladores > 400 líneas

---

Este documento recoge la lista de comprobaciones y cambios a realizar antes del lanzamiento oficial (producción).

## 1. Secrets & configuración
- Eliminar cualquier credencial de prueba hardcodeada en el código y en `config/`.
- Mover TODOS los secretos a variables de entorno (`.env`) y asegurar que `.env` no esté versionado.
- Rotar claves sensibles (APP_KEY, certificados, API keys de terceros) antes del deploy.
- Buscar en el repo por patrones: `password`, `passwd`, `secret`, `token`, `apikey`, `api_key`, `pwd`, `admin123` y revisar resultados.

## 2. Acceso y autenticación
- Asegurar `APP_ENV=production` y `APP_DEBUG=false`.
- Revisar `config/sanctum.php` y `SANCTUM_STATEFUL_DOMAINS` para dominios de producción.
- Decidir política de tokens: actualmente `login()` revoca tokens previos (`$usuario->tokens()->delete()`) — mantener o cambiar según necesidad.
- Asegurar que no se loguean tokens ni contraseñas en logs.

## 3. Autorización y RBAC
- Normalizar formato de slugs de permisos (`.` o `-`) y documentarlo en lugar central.
- Limpiar y pre-calentar cache de permisos antes del release:
  - `App::make(\App\Services\PermissionService::class)->warmupPermissionCache();`
- Revisar seeders de `roles` y `permisos`, eliminar usuarios/roles de prueba.

## 4. Multitenancy
- Documentar y validar los headers esperados (`X-Empresa-Id`, `X-Tenant-Id`) y comportamiento por subdominio.
- Eliminar datos/tanants de prueba y preparar seeders de producción controlados.
- Confirmar que jobs en cola sean tenant-aware cuando corresponda.

## 5. Seguridad de transporte y headers
- Forzar HTTPS / HSTS en reverse proxy o `SecurityHeaders` middleware.
- Ajustar `config/cors.php` para producir orígenes permitidos (no `*`).
- Habilitar CSP, X-Frame-Options, Referrer-Policy en `app/Http/Middleware/SecurityHeaders.php`.

## 6. Rate limiting & DoS
- Revisar límites aplicados (`throttle:5,1` para login, `throttle:120,1` para usuarios autenticados) y ajustar para producción.
- Verificar `RateLimitingService::isIPBlocked()` y reglas de bloqueo.
- Documentar manejo de `429` para clientes (usar `Retry-After`).

## 7. Documentación & OpenAPI
- Proteger UI de Swagger (no exponerla sin auth o IP allowlist) y poner `generate_always=false` en prod.
- Regenerar specs antes del release: `php artisan l5-swagger:generate`.
- Actualizar ejemplos curl para usar placeholders en lugar de credenciales reales.

## 8. Logs, errores y monitoring
- Poner DSN de Sentry y otros endpoints de monitoring en variables de entorno.
- No exponer stack traces ni información sensible en responses.
- Ajustar niveles de logging (no debug en prod).

## 9. Base de datos & migraciones
- Eliminar seeders de datos sensibles o moverlos a `seeds/dev`.
- Preparar y probar script de migración y rollback.
- Realizar backup antes del primer deploy.

## 10. Archivos y artefactos
- Verificar `.gitignore` incluye `.env*`, `storage/`, `vendor/`, `node_modules/`.
- Limpiar `storage/` de archivos temporales que contengan secretos.
- Mantener `.env.example` sin valores reales.

## 11. CI/CD y despliegue
- Usar secret stores en CI (GitHub Actions secrets, etc.).
- Incluir healthchecks y readiness endpoints y comprobarlos después del deploy.
- Documentar rollback paso a paso.

## 12. Tests y QA
- Ejecutar tests automatizados (unit/integration) que cubran auth, permisos y multitenancy.
- Hacer pruebas de carga en endpoints de escritura (pagos, imports).

## 13. Cambios concretos en ejemplos curl (usar placeholders)
- Login (no usar credenciales reales):

```bash
curl -X POST https://api.tu-dominio/api/login \
  -H "Content-Type: application/json" \
  -d '{"email":"<ADMIN_EMAIL>","password":"<PASSWORD>"}'
```

- Usar tenant header y token:

```bash
curl -H "Authorization: Bearer <TOKEN>" \
     -H "X-Empresa-Id: <EMPRESA_ID>" \
     https://api.tu-dominio/api/productos
```

- Logout:

```bash
curl -X POST https://api.tu-dominio/api/logout \
  -H "Authorization: Bearer <TOKEN>" \
  -H "X-Empresa-Id: <EMPRESA_ID>"
```

- Manejo de 429:
  - Leer header `Retry-After` y reintentar respetando ese tiempo.

## 14. Operaciones post-deploy
- Ejecutar migraciones y limpiar caches:

```bash
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan l5-swagger:generate
```

- Warmup permisos (artisanal):

```php
App::make(\App\Services\PermissionService::class)->warmupPermissionCache();
```

## 15. Auditoría final (comandos útiles)
- Buscar secretos en el repo:

```bash
git grep -nE "password|passwd|secret|token|apikey|api_key|pwd|admin123" || true
```

- Verificar archivos publicados:

```bash
ls -la storage api-docs
```

---

**Notas finales:**
- Mantener checklist en `docs/` y agregar un paso del pipeline CI que ejecute el grep de secretos y falle si encuentra coincidencias.
- Documentar cualquier cambio de última hora en el release notes.