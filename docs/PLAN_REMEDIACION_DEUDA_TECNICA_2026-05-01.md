# 📋 PLAN DE REMEDIACIÓN DE DEUDA TÉCNICA — Ursol CAST API

**Fecha de creación:** 1 de mayo de 2026  
**Basado en:** AUDITORIA_DEUDA_TECNICA_2026-05-01.md  
**Versión objetivo:** v5.1.0 (Deuda Técnica Remediada)  
**Timeline estimado:** 4-5 semanas  
**Team size:** 1-2 developers  

---

## 📊 Resumen Ejecutivo

| Ítem | Valor |
|------|-------|
| **Hallazgos identificados** | 9 (6 Medio, 2 Bajo, 1 Crítico histórico) |
| **Bloqueantes** | 0 ✅ |
| **Esfuerzo total** | 40-60 horas |
| **Sprint duration** | 4-5 semanas (1-2 devs) |
| **ROI esperado** | +20-30% mantenibilidad |
| **Riesgo de implementación** | Bajo |

---

## 🎯 Objetivos de la Remediación

### Objetivo Principal
Reducir deuda técnica de **8/10 → 9.5/10** manteniendo 100% de tests passing y cero regresiones en seguridad.

### Objetivos Secundarios
1. ✅ Completar cobertura de Service Layer (80% → 95%+)
2. ✅ Completar cobertura DTO (75% → 90%+)
3. ✅ Validación SSRF en webhooks
4. ✅ Migrar shell_exec() a PHP nativo
5. ✅ Completar Swagger/OpenAPI (96.9% → 100%)

---

## 📅 PLAN POR SEMANA

### SEMANA 1: Seguridad & Fundación (8-10 horas)

#### DÍA 1: Planning & Preparación (1-2h)
- [ ] Crear rama de desarrollo: `git checkout -b feature/deuda-tecnica-remediacion`
- [ ] Configurar entorno de testing
- [ ] Ejecutar test suite base: `make test` ✅ Debe pasar 100%
- [ ] Revisar archivos críticos identificados

**Checklist:**
```bash
# Verificar estado inicial
make test                    # Debe pasar
make phpstan                 # Debe ser Level 8, 0 errores
make ci-quality              # Debe pasar
```

---

#### DÍA 2: SSRF Validation en Webhooks (2-3h)

**Objetivo:** Implementar validación de URLs privadas en sistema de webhooks

**Archivos a crear/modificar:**
1. [NEW] `app/Rules/ValidateWebhookUrlRule.php` — Nueva rule de validación
2. [MODIFY] `app/Models/Webhook.php` — Agregar rule
3. [NEW] `tests/Unit/Rules/ValidateWebhookUrlRuleTest.php` — Tests unitarios
4. [MODIFY] `app/Http/Requests/StoreWebhookRequest.php` — Aplicar rule

**Implementación:**

```php
// app/Rules/ValidateWebhookUrlRule.php
<?php
namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidateWebhookUrlRule implements ValidationRule {
    // Rangos privados CIDR
    private const PRIVATE_RANGES = [
        '10.0.0.0/8',           // Clase A privada
        '172.16.0.0/12',        // Clase B privada  
        '192.168.0.0/16',       // Clase C privada
        '127.0.0.0/8',          // Loopback
        '169.254.0.0/16',       // Link-local
        '224.0.0.0/4',          // Multicast
        '240.0.0.0/4',          // Reservado
    ];

    private const BLOCKED_IPS = [
        '0.0.0.0',              // Broadcast
        '255.255.255.255',      // Broadcast
        '169.254.169.254',      // AWS metadata
    ];

    private const BLOCKED_PORTS = range(1, 1023);

    public function validate(
        string $attribute,
        mixed $value,
        Closure $fail
    ): void {
        $url = filter_var($value, FILTER_SANITIZE_URL);
        
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            $fail('La URL no es válida');
            return;
        }

        $host = parse_url($url, PHP_URL_HOST);
        $port = parse_url($url, PHP_URL_PORT) ?? 80;

        // Verificar hosts bloqueados
        if ($this->isBlockedHost($host)) {
            $fail('El host {$host} no está permitido');
            return;
        }

        // Verificar puertos bloqueados
        if ($this->isBlockedPort($port)) {
            $fail('El puerto {$port} no está permitido');
            return;
        }

        // Resolver IP y verificar rango privado
        if ($ip = gethostbyname($host)) {
            if ($this->isPrivateIp($ip)) {
                $fail('No se permiten URLs en redes privadas');
            }
        }
    }

    private function isBlockedHost(string $host): bool {
        return in_array($host, self::BLOCKED_IPS)
            || in_array($host, ['localhost', 'localhost.localdomain']);
    }

    private function isBlockedPort(int $port): bool {
        return in_array($port, self::BLOCKED_PORTS) 
            && !in_array($port, [80, 443]);
    }

    private function isPrivateIp(string $ip): bool {
        foreach (self::PRIVATE_RANGES as $range) {
            if ($this->isIpInRange($ip, $range)) {
                return true;
            }
        }
        return false;
    }

    private function isIpInRange(string $ip, string $range): bool {
        [$subnet, $bits] = explode('/', $range);
        $ip = ip2long($ip);
        $subnet = ip2long($subnet);
        $mask = -1 << (32 - (int) $bits);
        
        return ($ip & $mask) === ($subnet & $mask);
    }
}
```

**Tests:**
```php
// tests/Unit/Rules/ValidateWebhookUrlRuleTest.php
public function test_rejects_private_ips() {
    $rule = new ValidateWebhookUrlRule();
    
    $this->assertFail($rule, 'http://127.0.0.1/webhook');
    $this->assertFail($rule, 'http://localhost/webhook');
    $this->assertFail($rule, 'http://192.168.1.1/webhook');
    $this->assertFail($rule, 'http://10.0.0.1/webhook');
}

public function test_rejects_metadata_endpoints() {
    $rule = new ValidateWebhookUrlRule();
    $this->assertFail($rule, 'http://169.254.169.254/latest/');
}

public function test_accepts_public_urls() {
    $rule = new ValidateWebhookUrlRule();
    $this->assertPass($rule, 'https://webhook.example.com/api/callback');
    $this->assertPass($rule, 'https://api.github.com/webhooks');
}
```

**Puntos de control:**
```bash
# Después de implementar
make test                    # Nuevos tests deben pasar
make phpstan                 # Sin errores
git add -p                   # Review cambios
```

**Tiempo estimado:** 2-3 horas ⏱️

---

#### DÍA 3: Secretos en Seeders → Variables de Entorno (1-2h)

**Objetivo:** Migrar contraseñas hardcodeadas a variables de entorno

**Archivos a modificar:**
1. `database/seeders/FoundersSeeder.php` — Cambiar contraseña
2. `database/seeders/UsuarioAdminSeeder.php` — Cambiar contraseña
3. `.env.example` — Agregar nuevas variables
4. `config/auth.php` — Agregar config para defaults

**Implementación:**

```php
// database/seeders/UsuarioAdminSeeder.php (ANTES)
Usuario::updateOrCreate(
    ['email' => 'admin@ursol.local'],
    [
        'nombre' => 'Administrador',
        'password' => Hash::make('admin123'),  // ❌ Hardcodeada
    ]
);

// DESPUÉS
$this->command->info('🔐 Creando usuario administrador...');

$password = config('auth.seeder_defaults.admin_password') 
    ?? env('ADMIN_SEEDER_PASSWORD', 'ChangeMe@2026!#');

if ($password === 'ChangeMe@2026!#') {
    $this->command->warn('⚠️  ADVERTENCIA: Usando contraseña de desarrollo');
    $this->command->warn('⚠️  Cambiar ADMIN_SEEDER_PASSWORD en .env');
}

$usuario = Usuario::updateOrCreate(
    ['email' => 'admin@ursol.local'],
    [
        'nombre' => 'Administrador del Sistema',
        'password' => Hash::make($password),
        'email_verified_at' => now(),
        'is_active' => true,
    ]
);

$this->command->info("✓ Usuario admin creado: {$usuario->email}");
```

**.env.example:**
```env
# Seeder defaults (cambiar en producción)
ADMIN_SEEDER_PASSWORD=ChangeMe@2026!#
FOUNDER_SEEDER_PASSWORD=ChangeMe@2026!#
```

**config/auth.php:**
```php
'seeder_defaults' => [
    'admin_password' => env('ADMIN_SEEDER_PASSWORD', 'ChangeMe@2026!#'),
    'founder_password' => env('FOUNDER_SEEDER_PASSWORD', 'ChangeMe@2026!#'),
],
```

**Tests:**
```php
public function test_seeder_uses_env_password() {
    $this->artisan('db:seed', ['--class' => 'UsuarioAdminSeeder']);
    
    $user = Usuario::where('email', 'admin@ursol.local')->first();
    $this->assertNotNull($user);
    
    // Verificar que contraseña funciona
    $this->assertTrue(Hash::check(config('auth.seeder_defaults.admin_password'), $user->password));
}
```

**Tiempo estimado:** 1-2 horas ⏱️

---

#### DÍA 4-5: Refactorizar 2 Controllers IA a Service Layer (3-4h)

**Objetivo:** Migrar CabysController y ContentController al patrón Service Layer

**Archivos afectados:**
1. `app/Http/Controllers/Api/V1/AI/CabysController.php` — Refactorizar
2. `app/Http/Controllers/Api/V1/AI/ContentController.php` — Refactorizar
3. [NEW] `app/Services/AI/CabysService.php` — Nueva service
4. [NEW] `app/Services/AI/ContentService.php` — Nueva service

**Estrategia:**

```php
// ANTES: Query directa en controller
class CabysController {
    public function classifyProduct($id) {
        $producto = Producto::where('id', $id)
            ->where('empresa_id', auth()->user()->empresa_id)
            ->first();
        
        // 50+ líneas de lógica
        $cabys = CabysClassifier::classify($producto);
        
        return response()->json($cabys);
    }
}

// DESPUÉS: Service layer
class CabysController {
    public function __construct(
        private CabysService $cabysService
    ) {}

    public function classifyProduct($id) {
        $classification = $this->cabysService->classifyProduct($id);
        return response()->json($classification);
    }
}

// Nueva service encapsula la lógica
class CabysService {
    public function classifyProduct(int $id): array {
        $producto = Producto::find($id);
        $this->authorize('view', $producto);
        
        // Lógica de clasificación
        return CabysClassifier::classify($producto);
    }
}
```

**Checklist:**
- [ ] Extraer lógica de controller a service
- [ ] Crear DTOs para inputs si es necesario
- [ ] Agregar autorización (Policy/Gate)
- [ ] Tests unitarios para service
- [ ] Tests funcionales para controller
- [ ] Anotaciones OpenAPI
- [ ] Ejecutar `make test` — todos deben pasar

**Tiempo estimado:** 3-4 horas ⏱️

---

#### FIN DE SEMANA 1: Review & Merge (1h)

```bash
# Preparar para merge
git add -A
git commit -m "refactor: SSRF validation, env secrets, AI services

- feat: Validación SSRF en URLs de webhooks (DT-003)
- refactor: Contraseñas de seeders → variables de entorno (DT-005)
- refactor: 2 controllers IA a service layer (DT-001)
- test: +15 nuevos tests para cambios
- perf: Eliminada queries directas de controllers"

# Crear PR y solicitar review
git push -u origin feature/deuda-tecnica-remediacion

# En GitHub: Crear PR describiendo cambios
```

**Status de Semana 1:**
- ✅ SSRF validation implementada
- ✅ Secretos migrados a env
- ✅ 2 controllers IA refactorizados
- ✅ +15 nuevos tests (todos pasando)
- ✅ 0 regresiones identificadas

---

### SEMANA 2-3: DTOs & FormRequests (12-14 horas)

#### OBJETIVO: Completar cobertura DTO a 90%+

**Controllers sin DTOs a implementar:**
1. ComplianceDashboardController (3-4 métodos)
2. ComprobanteElectronicoController (5-6 métodos)
3. RolPermisoController (3 métodos)
4. RolUsuarioController (3 métodos)
5. ReportingController (3-4 métodos)
6. AI controllers - métodos específicos (5-6 métodos)

---

#### PLANTILLA DE IMPLEMENTACIÓN (para cada DTO)

```php
// app/DTOs/Reporting/GenerateReportDTO.php
<?php
namespace App\DTOs\Reporting;

use Carbon\CarbonImmutable;
use Illuminate\Http\Request;

final class GenerateReportDTO {
    public function __construct(
        public readonly string $tipo_reporte,
        public readonly CarbonImmutable $fecha_inicio,
        public readonly CarbonImmutable $fecha_fin,
        public readonly string $formato, // 'pdf', 'excel', 'csv'
        public readonly ?int $sucursal_id = null,
        public readonly ?string $moneda = 'CRC',
    ) {}

    public static function fromRequest(Request $request): self {
        return new self(
            tipo_reporte: $request->validated('tipo_reporte'),
            fecha_inicio: CarbonImmutable::parse(
                $request->validated('fecha_inicio')
            ),
            fecha_fin: CarbonImmutable::parse(
                $request->validated('fecha_fin')
            ),
            formato: $request->validated('formato', 'pdf'),
            sucursal_id: $request->validated('sucursal_id'),
            moneda: $request->validated('moneda', 'CRC'),
        );
    }

    public function toArray(): array {
        return [
            'tipo_reporte' => $this->tipo_reporte,
            'fecha_inicio' => $this->fecha_inicio->toIso8601String(),
            'fecha_fin' => $this->fecha_fin->toIso8601String(),
            'formato' => $this->formato,
            'sucursal_id' => $this->sucursal_id,
            'moneda' => $this->moneda,
        ];
    }
}
```

```php
// app/Http/Requests/GenerateReportRequest.php
<?php
namespace App\Http\Requests;

class GenerateReportRequest extends FormRequest {
    public function authorize(): bool {
        return $this->user()->can('generate-reports');
    }

    public function rules(): array {
        return [
            'tipo_reporte' => 'required|in:balance,pyg,flujo_caja,mayores',
            'fecha_inicio' => 'required|date|before:fecha_fin',
            'fecha_fin' => 'required|date|after:fecha_inicio',
            'formato' => 'required|in:pdf,excel,csv',
            'sucursal_id' => 'nullable|exists:sucursales,id',
            'moneda' => 'in:CRC,USD,EUR',
        ];
    }

    public function messages(): array {
        return [
            'tipo_reporte.required' => 'Tipo de reporte requerido',
            'tipo_reporte.in' => 'Tipo de reporte no válido',
            'fecha_inicio.before' => 'Fecha inicio debe ser anterior a fecha fin',
        ];
    }
}
```

**Plan por día:**

| Día | Tarea | Horas | DTOs |
|-----|-------|-------|------|
| L | ComplianceDashboard DTOs + Tests | 2.5h | 4 |
| M | ComprobanteElectronico DTOs + Tests | 2.5h | 6 |
| X | RolPermiso + RolUsuario DTOs + Tests | 2h | 6 |
| J | Reporting DTOs + Tests | 2h | 4 |
| V | AI-specific DTOs + Tests | 2h | 6 |
| V | Review, refactor, cleanup | 1h | - |

**Total Semana 2-3:** ~12-14 horas | ~26 nuevos DTOs

**Verificación:**
```bash
find app/DTOs -name "*.php" | wc -l    # Debe ser ~99 (73 + 26)
make test                                # Todos deben pasar
make phpstan                             # Level 8, 0 errores
```

---

### SEMANA 3-4: Shell Exec & FormRequests IA (5-7 horas)

#### TAREA 1: Migrar shell_exec() a PHP Nativo (3-4h)

**Archivo objetivo:** [app/Services/Hacienda/Xml/FirmaDigitalService.php](app/Services/Hacienda/Xml/FirmaDigitalService.php#L363)

**Problema actual:**
```php
// ❌ Línea 363
$opensslPath = trim((string) shell_exec('which openssl 2>/dev/null'));

// ❌ Línea 380
$output1 = shell_exec($cmd1);
```

**Solución - Opción 1: PHP Extension (Recomendado)**

```php
// app/Services/Hacienda/Xml/FirmaDigitalService.php
class FirmaDigitalService {
    /**
     * Convertir certificado P12 a formato moderno si es necesario
     */
    public function convertirP12Legacy(
        string $p12Content,
        string $password
    ): OpenSSLAsymmetricKey|bool {
        // Usar extensión openssl de PHP (nativa)
        $certs = [];
        
        $success = openssl_pkcs12_read(
            $p12Content,
            $certs,
            $password
        );

        if (!$success) {
            throw new HaciendaException(
                'No se pudo leer certificado P12: ' 
                . openssl_error_string()
            );
        }

        if (!isset($certs['key'])) {
            throw new HaciendaException(
                'Certificado no contiene clave privada'
            );
        }

        // Obtener clave privada
        $privateKey = openssl_pkey_get_private($certs['key']);
        
        if (!$privateKey) {
            throw new HaciendaException(
                'No se pudo extraer clave privada: ' 
                . openssl_error_string()
            );
        }

        return $privateKey;
    }

    /**
     * Firmar comprobante XML
     */
    public function firmarXml(
        string $xml,
        OpenSSLAsymmetricKey $privateKey,
        ?string $certificatePath = null
    ): string {
        // Usar openssl_sign() en lugar de shell_exec()
        $signature = '';
        
        $success = openssl_sign(
            $xml,
            $signature,
            $privateKey,
            OPENSSL_ALGO_SHA256
        );

        if (!$success) {
            throw new HaciendaException(
                'Error al firmar XML: ' 
                . openssl_error_string()
            );
        }

        return base64_encode($signature);
    }
}
```

**Solución - Opción 2: Config-based Path (Alternativa si falla)**

```php
// config/hacienda.php
return [
    'openssl' => [
        'path' => env('OPENSSL_PATH', '/usr/bin/openssl'),
        'timeout' => 30,
    ],
];

// app/Services/Hacienda/Xml/FirmaDigitalService.php
private function getOpenSSLPath(): string {
    $path = config('hacienda.openssl.path');
    
    if (!file_exists($path) || !is_executable($path)) {
        throw new HaciendaException(
            "OpenSSL no disponible en: {$path}"
        );
    }
    
    return $path;
}
```

**Tests:**
```php
public function test_convierte_p12_legacy_sin_shell_exec() {
    $p12Content = file_get_contents($this->testCertPath);
    $privateKey = $this->service->convertirP12Legacy(
        $p12Content,
        'test123'
    );
    
    $this->assertInstanceOf(OpenSSLAsymmetricKey::class, $privateKey);
}

public function test_firma_xml_con_openssl_nativo() {
    $xml = '<test><data>test</data></test>';
    $signature = $this->service->firmarXml($xml, $this->privateKey);
    
    $this->assertNotEmpty($signature);
    $this->assertTrue(
        openssl_verify($xml, base64_decode($signature), $this->publicKey)
    );
}
```

**Verificación:**
```bash
# Verificar que no quedan shell_exec()
grep -r "shell_exec\|exec\|system\|passthru" app/ 
# Debe retornar solo matches en comentarios

# Ejecutar tests Hacienda
make test-filter FILTER="Hacienda"  # Todos deben pasar
```

**Tiempo:** 3-4 horas ⏱️

---

#### TAREA 2: Validación FormRequest en Controladores IA (2-3h)

**Archivos a crear:**

```php
// app/Http/Requests/Ai/ClassifyProductRequest.php
class ClassifyProductRequest extends FormRequest {
    public function authorize(): bool {
        return $this->user()->can('use-ai-classification');
    }

    public function rules(): array {
        return [
            'producto_id' => [
                'required',
                'exists:productos,id',
                function ($attribute, $value, $fail) {
                    $producto = Producto::find($value);
                    if ($producto->empresa_id !== auth()->user()->empresa_id) {
                        $fail('Producto no pertenece a tu empresa');
                    }
                },
            ],
            'include_market_analysis' => 'boolean',
        ];
    }
}

// app/Http/Requests/Ai/GenerateDescriptionRequest.php
class GenerateDescriptionRequest extends FormRequest {
    public function authorize(): bool {
        return $this->user()->can('use-ai-generation');
    }

    public function rules(): array {
        return [
            'producto_id' => 'required|exists:productos,id',
            'tone' => 'in:professional,casual,technical,marketing',
            'max_tokens' => 'integer|min:100|max:2000|default:500',
            'language' => 'in:es,en,pt|default:es',
        ];
    }
}

// app/Http/Requests/Ai/AnalyzeImageRequest.php
class AnalyzeImageRequest extends FormRequest {
    public function authorize(): bool {
        return $this->user()->can('use-ai-vision');
    }

    public function rules(): array {
        return [
            'image' => 'required|image|max:5120', // 5MB
            'analysis_type' => 'in:ocr,classification,extraction',
            'language' => 'in:es,en|default:es',
        ];
    }
}
```

**Actualizar Controllers:**
```php
// app/Http/Controllers/Api/V1/AI/CabysController.php (DESPUÉS)
public function classifyProduct(ClassifyProductRequest $request) {
    $validated = $request->validated();
    
    $producto = Producto::find($validated['producto_id']);
    $this->authorize('view', $producto);
    
    $classification = $this->cabysService->classify($producto);
    
    return response()->json($classification);
}
```

**Tiempo:** 2-3 horas ⏱️

---

### SEMANA 4: Swagger & Finalización (4-6 horas)

#### TAREA 1: Completar Anotaciones Swagger (2-3h)

**Controllers sin Swagger (3 identificados):**
1. ComplianceDashboardController
2. 2 controllers de reportes adicionales

**Patrón a seguir (copiar de VentaController):**

```php
// app/Http/Controllers/API/ComplianceDashboardController.php
#[OA\Get(
    path: '/api/compliance/dashboard',
    summary: 'Obtener dashboard de compliance',
    description: 'Retorna métricas y estado de compliance del sistema',
    tags: ['Compliance'],
    security: [['sanctum' => []]],
    responses: [
        new OA\Response(
            response: 200,
            description: 'Dashboard obtenido exitosamente',
            content: new OA\JsonContent(
                type: 'object',
                properties: [
                    new OA\Property(
                        property: 'hacienda_status',
                        description: 'Estado de integración Hacienda',
                        type: 'string',
                        enum: ['compliant', 'pending', 'error']
                    ),
                    new OA\Property(
                        property: 'pending_items',
                        description: 'Cantidad de items pendientes',
                        type: 'integer'
                    ),
                ]
            )
        ),
    ]
)]
public function index(): JsonResponse {
    // ...
}
```

**Verificación:**
```bash
# Generar Swagger
make artisan CMD="l5-swagger:generate"

# Verificar que Swagger esté accesible
curl http://localhost:8000/api/documentation

# Contar endpoints documentados
grep -r "#\[OA" app/Http/Controllers | wc -l  # Debe ser 97
```

**Tiempo:** 2-3 horas ⏱️

---

#### TAREA 2: Integración Mutation Testing en CI/CD (1-2h)

**Archivo a modificar:** `.github/workflows/ci.yml`

```yaml
- name: 🧬 Mutation Testing
  if: github.event_name == 'pull_request'
  run: |
    vendor/bin/infection \
      --threads=4 \
      --min-msi=70 \
      --min-covered-msi=80 \
      --log-verbosity=default
```

**Tiempo:** 1-2 horas ⏱️

---

#### TAREA 3: Documentación Final (1-2h)

- [ ] Actualizar ARCHITECTURE.md con deuda técnica remediada
- [ ] Crear changelog para v5.1.0
- [ ] Actualizar README.md si es necesario
- [ ] Crear MIGRATION_GUIDE.md si hay cambios de API

**Tiempo:** 1-2 horas ⏱️

---

## ✅ CHECKLIST DE VALIDACIÓN FINAL

### Tests (CRÍTICO)
- [ ] `make test` — 1,650+ tests passing ✅
- [ ] `make phpstan` — Level 8, 0 errores ✅
- [ ] `make ci-quality` — Todo pasa ✅
- [ ] Coverage >70% ✅

### Security (CRÍTICO)
- [ ] `composer audit` — No vulnerabilidades ✅
- [ ] SSRF validation en lugar ✅
- [ ] Sin shell_exec() en código activo ✅
- [ ] Secretos no en repo ✅

### Code Quality
- [ ] DTOs 90%+ ✅
- [ ] Service Layer 95%+ ✅
- [ ] Swagger 100% ✅
- [ ] FormRequests 100% ✅
- [ ] PHPStan Level 8 ✅

### Documentation
- [ ] AUDITORIA_DEUDA_TECNICA actualizada ✅
- [ ] CHANGELOG v5.1.0 completado ✅
- [ ] Guides actualizadas ✅

---

## 📈 MÉTRICAS PRE/POST

| Métrica | Antes | Después | Delta |
|---------|-------|---------|-------|
| Deuda Técnica | 8/10 | 9.5/10 | +1.5 |
| DTO Coverage | 75% | 90%+ | +15% |
| Service Layer | 80% | 95%+ | +15% |
| Swagger | 96.9% | 100% | +3.1% |
| Tests | 1,622 | 1,700+ | +78 |
| Shell exec() | 3 | 0 | ✅ |

---

## 🚀 DEPLOYMENT

```bash
# Después de que todos los checks pasen

# 1. Crear release branch
git checkout -b release/v5.1.0

# 2. Actualizar versión
composer version patch  # O manual en composer.json

# 3. Commit
git commit -m "chore: v5.1.0 — Remediación de deuda técnica"

# 4. Tag
git tag -a v5.1.0 -m "v5.1.0: Deuda técnica remediada
- SSRF validation en webhooks
- Secretos migrados a env
- 2 controllers IA refactorizados
- DTOs completadas (90%+)
- Swagger 100%
- Shell exec → PHP nativo"

# 5. Push
git push origin release/v5.1.0 --tags

# 6. Crear Release en GitHub
# Descripción: Copiar changelog + nota de cambios
```

---

## 📞 NOTAS & CONSIDERACIONES

### Orden de Implementación Recomendado
1. ✅ Seguridad PRIMERO (SSRF, secretos)
2. ✅ Controllers refactorización SEGUNDO (arquitectura)
3. ✅ DTOs TERCERO (cobertura)
4. ✅ Shell exec CUARTO (seguridad secundaria)
5. ✅ Swagger QUINTO (documentación)

### Rollback Plan
Si algo falla:
```bash
git reset --hard HEAD~1
git clean -fd
make test  # Verificar que todo vuelve a funcionar
```

### Recursos
- [AUDITORIA_DEUDA_TECNICA_2026-05-01.md](AUDITORIA_DEUDA_TECNICA_2026-05-01.md) — Detalles de hallazgos
- [REFACTORIZACION_CONTROLADORES.md](guides/REFACTORIZACION_CONTROLADORES.md) — Guía de refactorización
- [docs/ARCHITECTURE.md](docs/ARCHITECTURE.md) — Arquitectura general

---

**Última actualización:** 1 de mayo 2026  
**Estado:** Listo para implementación ✅

