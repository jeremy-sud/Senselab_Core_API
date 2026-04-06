# Guía de Integración de Webhooks — Ursol CAST API

## Introducción

Ursol CAST API permite configurar webhooks para recibir notificaciones en tiempo real cuando ocurren eventos importantes en su sistema. Esta guía explica cómo configurar, verificar y consumir webhooks.

---

## Eventos Disponibles

| Evento | Descripción | Origen |
|---|---|---|
| `venta.creada` | Se registra una nueva venta | `VentaService::crear()` |
| `factura.emitida` | Una factura electrónica es emitida/aceptada | `ComprobanteElectronicoService::cambiarEstado()` |
| `pago.recibido` | Se registra un nuevo pago | `PagoService::crear()` |
| `inventario.bajo` | Stock de un producto cae por debajo del mínimo | `SalidaInventarioService::procesar()` |
| `cliente.creado` | Se registra un nuevo cliente | `ClienteService::crear()` |

---

## Configuración de Webhooks

### Crear un Webhook

```http
POST /api/v1/webhooks
Authorization: Bearer {token}
Content-Type: application/json

{
  "nombre": "Mi integración",
  "url": "https://mi-servidor.com/webhooks/ursol",
  "eventos": ["venta.creada", "pago.recibido"],
  "descripcion": "Notificaciones de ventas y pagos",
  "timeout_segundos": 30,
  "max_reintentos": 3
}
```

**Campos:**

| Campo | Tipo | Requerido | Descripción |
|---|---|---|---|
| `nombre` | string | Sí | Nombre descriptivo (máx. 100 caracteres) |
| `url` | string | Sí | URL HTTPS donde se enviarán las notificaciones |
| `eventos` | array | Sí | Lista de eventos a escuchar |
| `descripcion` | string | No | Descripción del webhook |
| `timeout_segundos` | integer | No | Timeout de la petición (5-60s, default: 30) |
| `max_reintentos` | integer | No | Máximo de reintentos en caso de fallo (1-10, default: 3) |

**Respuesta:**

```json
{
  "success": true,
  "code": 201,
  "message": "Webhook creado exitosamente",
  "data": {
    "id": 1,
    "nombre": "Mi integración",
    "url": "https://mi-servidor.com/webhooks/ursol",
    "eventos": ["venta.creada", "pago.recibido"],
    "activo": true,
    "secret": "generado-automaticamente-64-chars"
  }
}
```

> **Importante:** El `secret` solo se muestra al crear el webhook. Guárdelo de forma segura.

### Listar Webhooks

```http
GET /api/v1/webhooks
Authorization: Bearer {token}
```

### Actualizar Webhook

```http
PUT /api/v1/webhooks/{id}
Authorization: Bearer {token}
Content-Type: application/json

{
  "eventos": ["venta.creada", "pago.recibido", "cliente.creado"]
}
```

### Eliminar Webhook

```http
DELETE /api/v1/webhooks/{id}
Authorization: Bearer {token}
```

### Regenerar Secret

```http
POST /api/v1/webhooks/{id}/regenerar-secret
Authorization: Bearer {token}
```

### Probar Webhook

```http
POST /api/v1/webhooks/{id}/test
Authorization: Bearer {token}
```

Envía un payload de prueba a la URL configurada.

### Ver Eventos Disponibles

```http
GET /api/v1/webhooks/eventos-disponibles
Authorization: Bearer {token}
```

---

## Formato del Payload

Cada notificación se envía como un `POST` HTTP con el siguiente formato:

```json
{
  "evento": "venta.creada",
  "empresa_id": 1,
  "timestamp": "2026-04-06T10:30:00Z",
  "payload": {
    "venta_id": 42,
    "numero_comprobante": "FE-2026-00042",
    "monto_total": 15000.50,
    "cliente_id": 7,
    "estado": "pendiente"
  }
}
```

### Payloads por Evento

#### `venta.creada`
```json
{
  "venta_id": 42,
  "numero_comprobante": "FE-2026-00042",
  "monto_total": 15000.50,
  "cliente_id": 7,
  "estado": "pendiente"
}
```

#### `factura.emitida`
```json
{
  "comprobante_id": 15,
  "clave_numerica": "50601042600012345678901001...",
  "tipo_comprobante": "01",
  "estado": "aceptado",
  "venta_id": 42
}
```

#### `pago.recibido`
```json
{
  "pago_id": 33,
  "monto": 5000.00,
  "estado": "pendiente",
  "forma_pago_id": 1,
  "cliente_id": 7,
  "proveedor_id": null
}
```

#### `inventario.bajo`
```json
{
  "producto_id": 10,
  "nombre": "Tornillo hexagonal M8",
  "codigo": "TORN-M8",
  "stock_actual": 3.00,
  "stock_minimo": 10.00,
  "almacen_id": 2
}
```

#### `cliente.creado`
```json
{
  "cliente_id": 55,
  "nombre": "Juan Pérez",
  "numero_identificacion": "123456789",
  "email": "juan@ejemplo.com"
}
```

---

## Headers HTTP

Cada petición incluye los siguientes headers:

| Header | Descripción | Ejemplo |
|---|---|---|
| `Content-Type` | Siempre `application/json` | `application/json` |
| `X-Webhook-Signature` | Firma HMAC-SHA256 del body | `sha256=abc123...` |
| `X-Webhook-Event` | Nombre del evento | `venta.creada` |
| `X-Webhook-Delivery` | ID único de entrega | `uuid-v4` |
| `X-Webhook-Timestamp` | Timestamp Unix | `1712400600` |
| `User-Agent` | Identificación del sistema | `Ursol-CAST-API/4.2.0` |

---

## Verificación de Firma (HMAC-SHA256)

Para verificar que una notificación proviene de Ursol CAST API, valide la firma HMAC-SHA256:

### PHP
```php
function verificarFirmaWebhook(string $payload, string $signature, string $secret): bool
{
    $expected = 'sha256=' . hash_hmac('sha256', $payload, $secret);
    return hash_equals($expected, $signature);
}

// En su controlador:
$payload = file_get_contents('php://input');
$signature = $_SERVER['HTTP_X_WEBHOOK_SIGNATURE'] ?? '';
$secret = 'su-webhook-secret';

if (!verificarFirmaWebhook($payload, $signature, $secret)) {
    http_response_code(401);
    exit('Firma inválida');
}

$data = json_decode($payload, true);
// Procesar evento...
```

### Node.js
```javascript
const crypto = require('crypto');

function verificarFirma(payload, signature, secret) {
    const expected = 'sha256=' + crypto.createHmac('sha256', secret)
        .update(payload).digest('hex');
    return crypto.timingSafeEqual(
        Buffer.from(expected), Buffer.from(signature)
    );
}

// En su endpoint Express:
app.post('/webhooks/ursol', (req, res) => {
    const payload = JSON.stringify(req.body);
    const signature = req.headers['x-webhook-signature'];
    
    if (!verificarFirma(payload, signature, process.env.WEBHOOK_SECRET)) {
        return res.status(401).send('Firma inválida');
    }
    
    // Procesar evento...
    res.status(200).send('OK');
});
```

### Python
```python
import hmac
import hashlib

def verificar_firma(payload: bytes, signature: str, secret: str) -> bool:
    expected = 'sha256=' + hmac.new(
        secret.encode(), payload, hashlib.sha256
    ).hexdigest()
    return hmac.compare_digest(expected, signature)
```

---

## Política de Reintentos

Si su servidor no responde con un código HTTP 2xx, Ursol CAST API reintentará la entrega:

| Intento | Delay | Tiempo acumulado |
|---|---|---|
| 1 | Inmediato | 0s |
| 2 | 30 segundos | 30s |
| 3 | 2 minutos | 2m 30s |
| 4 (si max_reintentos ≥ 4) | 8 minutos | 10m 30s |

- **Backoff exponencial:** `30 × 4^(intento-1)` segundos
- **Máximo de reintentos:** Configurable (1-10, default: 3)
- **Timeout por petición:** Configurable (5-60s, default: 30s)
- **Códigos que disparan reintento:** Cualquier respuesta que no sea 2xx, o timeout/error de conexión

---

## Logs de Entrega

Consulte el historial de entregas de un webhook:

```http
GET /api/v1/webhooks/{id}/logs
Authorization: Bearer {token}
```

**Respuesta:**

```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "evento": "venta.creada",
      "estado": "exitoso",
      "codigo_respuesta": 200,
      "latencia_ms": 234,
      "intento": 1,
      "creado_en": "2026-04-06T10:30:01Z"
    }
  ]
}
```

**Estados posibles:** `pendiente`, `exitoso`, `fallido`

---

## Buenas Prácticas para Receptores

1. **Responda rápido** — Retorne `200 OK` lo antes posible. Procese el evento de forma asíncrona si la lógica es compleja.

2. **Verifique siempre la firma** — Nunca procese un webhook sin validar `X-Webhook-Signature`.

3. **Idempotencia** — Use `X-Webhook-Delivery` como clave de idempotencia para evitar procesar el mismo evento dos veces (los reintentos envían el mismo delivery ID).

4. **HTTPS obligatorio** — Solo se aceptan URLs con protocolo `https://`.

5. **Timeout** — Su endpoint debe responder dentro del timeout configurado (default: 30s) para evitar reintentos innecesarios.

---

## Permisos Requeridos

Los endpoints de webhooks requieren los siguientes permisos RBAC:

| Acción | Permiso |
|---|---|
| Listar webhooks | `ver-webhooks` |
| Crear webhook | `crear-webhooks` |
| Editar webhook | `editar-webhooks` |
| Eliminar webhook | `eliminar-webhooks` |

---

## Multi-tenancy

Los webhooks están aislados por empresa (tenant). Cada empresa solo puede ver y gestionar sus propios webhooks. Los eventos solo se despachan a webhooks de la misma empresa donde ocurrió el evento.
