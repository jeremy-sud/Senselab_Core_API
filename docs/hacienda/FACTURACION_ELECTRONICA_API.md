# API de Facturación Electrónica - Documentación

## Tabla de Contenidos
1. [Autenticación](#autenticación)
2. [Endpoints](#endpoints)
3. [Modelos de Datos](#modelos-de-datos)
4. [Códigos de Respuesta](#códigos-de-respuesta)
5. [Ejemplos de Uso](#ejemplos-de-uso)
6. [Webhooks](#webhooks)

---

## Autenticación

Todos los endpoints requieren autenticación mediante Laravel Sanctum.

### Obtener Token

```http
POST /api/login
Content-Type: application/json

{
  "email": "usuario@empresa.com",
  "password": "contraseña"
}
```

**Respuesta**:
```json
{
  "token": "1|xxxxxxxxxxxxxxxxxxxxxxxxxxxxxx",
  "user": {
    "id": 1,
    "name": "Usuario Test",
    "email": "usuario@empresa.com",
    "empresa_id": 1
  }
}
```

### Uso del Token

Incluir en header de cada request:
```http
Authorization: Bearer 1|xxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
```

---

## Endpoints

### 1. Listar Comprobantes

```http
GET /api/comprobantes
```

**Query Parameters**:
| Parámetro | Tipo | Descripción | Ejemplo |
|-----------|------|-------------|---------|
| `tipo_documento` | string | Filtrar por tipo (01-04) | `01` |
| `estado` | string | Filtrar por estado | `aceptado` |
| `fecha_desde` | date | Fecha inicio (Y-m-d) | `2025-01-01` |
| `fecha_hasta` | date | Fecha fin (Y-m-d) | `2025-01-31` |
| `clave` | string | Buscar por clave | `526112025...` |
| `consecutivo` | string | Buscar por consecutivo | `00000001` |
| `receptor_numero_identificacion` | string | Filtrar por receptor | `109876543` |
| `sort_by` | string | Ordenar por campo | `fecha_emision` |
| `sort_order` | string | Orden (asc/desc) | `desc` |
| `per_page` | integer | Registros por página | `15` |

**Ejemplo**:
```http
GET /api/comprobantes?estado=aceptado&per_page=20&sort_order=desc
```

**Respuesta (200 OK)**:
```json
{
  "current_page": 1,
  "data": [
    {
      "id": 1,
      "tipo_documento": "01",
      "clave": "52611202531011234567800000000000000000001154489877",
      "consecutivo": "00000000000000000001",
      "fecha_emision": "2025-11-26T10:30:00.000000Z",
      "estado": "aceptado",
      "receptor_nombre": "Cliente Test",
      "total_comprobante": 22600.00000,
      "fecha_respuesta": "2025-11-26T10:31:15.000000Z",
      "empresa": {
        "id": 1,
        "nombre_comercial": "Mi Empresa S.A."
      },
      "lineas_detalle": [...]
    }
  ],
  "first_page_url": "http://localhost/api/comprobantes?page=1",
  "from": 1,
  "last_page": 5,
  "last_page_url": "http://localhost/api/comprobantes?page=5",
  "next_page_url": "http://localhost/api/comprobantes?page=2",
  "path": "http://localhost/api/comprobantes",
  "per_page": 15,
  "prev_page_url": null,
  "to": 15,
  "total": 73
}
```

---

### 2. Crear Comprobante

```http
POST /api/comprobantes
Content-Type: application/json
```

**Body**:
```json
{
  "tipo_documento": "01",
  "consecutivo": "00000000000000000001",
  "fecha_emision": "2025-11-26T10:30:00",
  "condicion_venta": "01",
  "plazo_credito": null,
  "medio_pago": "01",
  "situacion": "1",
  
  "receptor_nombre": "Cliente de Prueba S.A.",
  "receptor_tipo_identificacion": "02",
  "receptor_numero_identificacion": "3101123456",
  "receptor_email": "cliente@empresa.com",
  "receptor_telefono": "88887777",
  "receptor_provincia": "1",
  "receptor_canton": "01",
  "receptor_distrito": "01",
  "receptor_barrio": "01",
  "receptor_otras_senas": "100 metros norte de la iglesia",
  
  "codigo_moneda": "CRC",
  "tipo_cambio": 1.00000,
  
  "observaciones": "Factura de prueba",
  
  "certificado_id": 1,
  
  "lineas": [
    {
      "numero_linea": 1,
      "codigo_tipo": "01",
      "codigo": "8523102100000",
      "cantidad": 2.00000,
      "unidad_medida": "Sp",
      "detalle": "Servicio de Consultoría Técnica",
      "precio_unitario": 50000.00000,
      "monto_total": 100000.00000,
      "monto_descuento": 5000.00000,
      "naturaleza_descuento": "Descuento por volumen",
      "subtotal": 95000.00000,
      "base_imponible": 95000.00000,
      "impuestos": [
        {
          "codigo": "01",
          "codigo_tarifa": "08",
          "tarifa": 13.00,
          "monto": 12350.00000
        }
      ],
      "monto_total_linea": 107350.00000
    },
    {
      "numero_linea": 2,
      "codigo": "8523102200000",
      "cantidad": 1.00000,
      "detalle": "Licencia de Software Anual",
      "precio_unitario": 200000.00000,
      "monto_total": 200000.00000,
      "subtotal": 200000.00000,
      "base_imponible": 200000.00000,
      "impuestos": [
        {
          "codigo": "01",
          "codigo_tarifa": "08",
          "tarifa": 13.00,
          "monto": 26000.00000
        }
      ],
      "monto_total_linea": 226000.00000
    }
  ]
}
```

**Respuesta (201 Created)**:
```json
{
  "message": "Comprobante creado y enviado a cola de procesamiento",
  "data": {
    "id": 5,
    "tipo_documento": "01",
    "clave": "52611202531011234567800000000000000000001154489877",
    "consecutivo": "00000000000000000001",
    "estado": "pendiente",
    "fecha_emision": "2025-11-26T10:30:00.000000Z",
    "total_comprobante": 333350.00000,
    "lineas_detalle": [...]
  }
}
```

**Validaciones**:
- `tipo_documento`: Requerido. Valores: 01, 02, 03, 04
- `consecutivo`: Requerido. Máximo 20 dígitos
- `condicion_venta`: Requerido. Valores: 01 (Contado), 02 (Crédito), 03 (Consignación), 04 (Apartado), 99 (Otro)
- `medio_pago`: Requerido. Valores: 01 (Efectivo), 02 (Tarjeta), 03 (Cheque), 04 (Transferencia), 05 (Recaudado por terceros), 99 (Otros)
- `lineas`: Requerido. Mínimo 1 línea
- `certificado_id`: Requerido. Debe existir y estar activo

---

### 3. Obtener Comprobante

```http
GET /api/comprobantes/{id}
```

**Respuesta (200 OK)**:
```json
{
  "id": 1,
  "empresa_id": 1,
  "tipo_documento": "01",
  "clave": "52611202531011234567800000000000000000001154489877",
  "consecutivo": "00000000000000000001",
  "fecha_emision": "2025-11-26T10:30:00.000000Z",
  "condicion_venta": "01",
  "medio_pago": "01",
  "situacion": "1",
  
  "receptor_nombre": "Cliente de Prueba S.A.",
  "receptor_numero_identificacion": "3101123456",
  "receptor_email": "cliente@empresa.com",
  
  "codigo_moneda": "CRC",
  "tipo_cambio": 1.00000,
  
  "total_venta_bruta": 300000.00000,
  "total_descuentos": 5000.00000,
  "total_venta_neta": 295000.00000,
  "total_impuestos": 38350.00000,
  "total_comprobante": 333350.00000,
  
  "estado": "aceptado",
  "fecha_envio": "2025-11-26T10:30:15.000000Z",
  "fecha_recibido": "2025-11-26T10:30:20.000000Z",
  "fecha_procesado": "2025-11-26T10:31:00.000000Z",
  "fecha_respuesta": "2025-11-26T10:31:15.000000Z",
  
  "mensaje_hacienda": "Comprobante aceptado",
  "codigo_respuesta_hacienda": "001",
  
  "intentos_envio": 1,
  
  "empresa": {
    "id": 1,
    "nombre_comercial": "Mi Empresa S.A.",
    "numero_identificacion": "3101123456"
  },
  
  "lineas_detalle": [
    {
      "id": 1,
      "numero_linea": 1,
      "codigo": "8523102100000",
      "cantidad": 2.00000,
      "detalle": "Servicio de Consultoría Técnica",
      "precio_unitario": 50000.00000,
      "monto_total": 100000.00000,
      "monto_descuento": 5000.00000,
      "subtotal": 95000.00000,
      "monto_total_linea": 107350.00000,
      "impuestos": [
        {
          "codigo": "01",
          "codigo_tarifa": "08",
          "tarifa": 13.00,
          "monto": 12350.00000
        }
      ]
    }
  ]
}
```

**Respuesta de Error (403 Forbidden)**:
```json
{
  "message": "No autorizado"
}
```

---

### 4. Descargar XML

```http
GET /api/comprobantes/{id}/xml?tipo=firmado
```

**Query Parameters**:
| Parámetro | Tipo | Descripción | Valores |
|-----------|------|-------------|---------|
| `tipo` | string | Tipo de XML | `original`, `firmado` |

**Respuesta (200 OK)**:
- Content-Type: `application/xml`
- Content-Disposition: `attachment; filename="clave_firmado.xml"`

**XML Retornado**:
```xml
<?xml version="1.0" encoding="UTF-8"?>
<FacturaElectronica xmlns="https://cdn.comprobanteselectronicos.go.cr/xml-schemas/v4.3/facturaElectronica" xmlns:ds="http://www.w3.org/2000/09/xmldsig#">
  <Clave>52611202531011234567800000000000000000001154489877</Clave>
  <CodigoActividad>620100</CodigoActividad>
  <NumeroConsecutivo>00000000000000000001</NumeroConsecutivo>
  <FechaEmision>2025-11-26T10:30:00-06:00</FechaEmision>
  ...
  <ds:Signature>...</ds:Signature>
</FacturaElectronica>
```

---

### 5. Reenviar Comprobante

```http
POST /api/comprobantes/{id}/reenviar
Content-Type: application/json
```

**Body**:
```json
{
  "certificado_id": 1
}
```

**Requisitos**:
- Comprobante debe estar en estado: `error`, `rechazado`, o `pendiente`

**Respuesta (200 OK)**:
```json
{
  "message": "Comprobante enviado a cola de procesamiento",
  "data": {
    "id": 1,
    "estado": "pendiente",
    "intentos_envio": 2
  }
}
```

**Respuesta de Error (400 Bad Request)**:
```json
{
  "message": "No se puede reenviar un comprobante en estado: aceptado"
}
```

---

### 6. Anular Comprobante (Nota Crédito)

```http
POST /api/comprobantes/{id}/anular
Content-Type: application/json
```

**Body**:
```json
{
  "razon_anulacion": "Error en facturación - Cambio de monto",
  "certificado_id": 1
}
```

**Requisitos**:
- Comprobante debe estar en estado: `aceptado`

**Respuesta (201 Created)**:
```json
{
  "message": "Nota crédito creada y enviada",
  "data": {
    "id": 10,
    "tipo_documento": "03",
    "consecutivo": "00000000000000000010",
    "tipo_documento_referencia": "01",
    "numero_documento_referencia": "00000000000000000001",
    "codigo_referencia": "01",
    "razon_referencia": "Error en facturación - Cambio de monto",
    "estado": "pendiente"
  }
}
```

**Códigos de Referencia**:
- `01`: Anula documento de referencia
- `02`: Corrige monto
- `03`: Devuelución de mercadería
- `04`: Sustitución de comprobante
- `05`: Referencia a otro documento
- `99`: Otros

---

### 7. Estadísticas

```http
GET /api/comprobantes/estadisticas/resumen
```

**Query Parameters**:
| Parámetro | Tipo | Descripción | Defecto |
|-----------|------|-------------|---------|
| `fecha_desde` | date | Fecha inicio | Hace 1 mes |
| `fecha_hasta` | date | Fecha fin | Hoy |

**Respuesta (200 OK)**:
```json
{
  "total_comprobantes": 150,
  "por_estado": {
    "aceptado": 120,
    "pendiente": 5,
    "procesando": 3,
    "rechazado": 10,
    "error": 12
  },
  "por_tipo": {
    "01": 100,
    "02": 5,
    "03": 40,
    "04": 5
  },
  "total_ventas": 45680000.00
}
```

---

## Modelos de Datos

### Tipo de Documento
| Código | Descripción |
|--------|-------------|
| `01` | Factura Electrónica |
| `02` | Nota de Débito Electrónica |
| `03` | Nota de Crédito Electrónica |
| `04` | Tiquete Electrónico |

### Condición de Venta
| Código | Descripción |
|--------|-------------|
| `01` | Contado |
| `02` | Crédito |
| `03` | Consignación |
| `04` | Apartado |
| `05` | Arrendamiento con opción de compra |
| `06` | Arrendamiento en función financiera |
| `99` | Otros |

### Medio de Pago
| Código | Descripción |
|--------|-------------|
| `01` | Efectivo |
| `02` | Tarjeta |
| `03` | Cheque |
| `04` | Transferencia - depósito bancario |
| `05` | Recaudado por terceros |
| `99` | Otros |

### Unidades de Medida
| Código | Descripción |
|--------|-------------|
| `Sp` | Servicios Profesionales |
| `m` | Metro |
| `kg` | Kilogramo |
| `s` | Segundo |
| `I` | Litro |
| `Os` | Onza |
| `Spe` | Servicios Especiales |
| `Alc` | Alcohólicas |
| `Cm` | Centímetro |
| `Otros` | Otros |

### Códigos de Impuesto
| Código | Descripción | Tarifa Común |
|--------|-------------|--------------|
| `01` | Impuesto al Valor Agregado | 13% |
| `02` | Impuesto Selectivo de Consumo | Variable |
| `03` | Impuesto Único a los Combustibles | Variable |
| `04` | Impuesto Específico de Bebidas Alcohólicas | Variable |
| `05` | Impuesto Específico sobre las Bebidas Envasadas sin Alcohol | Variable |
| `06` | Impuesto a los Productos de Tabaco | Variable |
| `07` | IVA (cálculo especial) | Variable |
| `08` | IVA Régimen de Bienes Usados | Variable |
| `99` | Otros | Variable |

### Estados de Comprobante
| Estado | Descripción |
|--------|-------------|
| `pendiente` | Creado, esperando envío |
| `enviando` | En proceso de envío a Hacienda |
| `recibido` | Recibido por Hacienda, en validación |
| `procesando` | Hacienda está procesando el comprobante |
| `aceptado` | ✅ Aprobado y aceptado por Hacienda |
| `rechazado` | ❌ Rechazado por Hacienda (ver mensaje_hacienda) |
| `error` | Error técnico en el proceso (revisar ultimo_error) |

---

## Códigos de Respuesta

### Exitosos
- `200 OK`: Solicitud procesada correctamente
- `201 Created`: Recurso creado exitosamente

### Errores del Cliente
- `400 Bad Request`: Datos inválidos o acción no permitida
- `401 Unauthorized`: Token no proporcionado o inválido
- `403 Forbidden`: Sin permisos para acceder al recurso
- `404 Not Found`: Recurso no encontrado
- `422 Unprocessable Entity`: Errores de validación

### Errores del Servidor
- `500 Internal Server Error`: Error interno del servidor
- `503 Service Unavailable`: Servicio temporalmente no disponible

---

## Ejemplos de Uso

### Ejemplo Completo: Factura con Descuento e IVA

```bash
curl -X POST http://localhost:8000/api/comprobantes \
  -H "Authorization: Bearer 1|xxxxx" \
  -H "Content-Type: application/json" \
  -d '{
    "tipo_documento": "01",
    "consecutivo": "00000000000000000005",
    "condicion_venta": "02",
    "plazo_credito": 30,
    "medio_pago": "04",
    "receptor_nombre": "Corporación XYZ S.A.",
    "receptor_tipo_identificacion": "02",
    "receptor_numero_identificacion": "3102654321",
    "receptor_email": "facturacion@xyz.com",
    "certificado_id": 1,
    "lineas": [
      {
        "numero_linea": 1,
        "codigo": "8523102100000",
        "cantidad": 10,
        "detalle": "Horas de Consultoría Senior",
        "precio_unitario": 75000,
        "monto_total": 750000,
        "monto_descuento": 75000,
        "naturaleza_descuento": "Descuento por volumen 10%",
        "subtotal": 675000,
        "base_imponible": 675000,
        "impuestos": [
          {
            "codigo": "01",
            "codigo_tarifa": "08",
            "tarifa": 13.00,
            "monto": 87750
          }
        ],
        "monto_total_linea": 762750
      }
    ]
  }'
```

### Ejemplo: Tiquete Electrónico (Sin Receptor)

```bash
curl -X POST http://localhost:8000/api/comprobantes \
  -H "Authorization: Bearer 1|xxxxx" \
  -H "Content-Type: application/json" \
  -d '{
    "tipo_documento": "04",
    "consecutivo": "00000000000000000100",
    "condicion_venta": "01",
    "medio_pago": "01",
    "certificado_id": 1,
    "lineas": [
      {
        "numero_linea": 1,
        "codigo": "1234567890123",
        "cantidad": 2,
        "detalle": "Café Americano Grande",
        "precio_unitario": 2500,
        "monto_total": 5000,
        "subtotal": 5000,
        "base_imponible": 5000,
        "impuestos": [
          {
            "codigo": "01",
            "codigo_tarifa": "08",
            "tarifa": 13.00,
            "monto": 650
          }
        ],
        "monto_total_linea": 5650
      }
    ]
  }'
```

---

## Webhooks

### Configurar Webhook (Próximamente)

Permitirá recibir notificaciones cuando cambie el estado de un comprobante.

```json
POST /api/webhooks
{
  "url": "https://tu-sistema.com/api/hacienda/webhook",
  "events": ["comprobante.aceptado", "comprobante.rechazado"],
  "secret": "tu_secreto_para_validar_firma"
}
```

**Payload del Webhook**:
```json
{
  "event": "comprobante.aceptado",
  "timestamp": "2025-11-26T10:31:15Z",
  "data": {
    "comprobante_id": 1,
    "clave": "52611202531011234567800000000000000000001154489877",
    "estado": "aceptado",
    "mensaje_hacienda": "Comprobante aceptado"
  },
  "signature": "sha256_hash_del_payload"
}
```

---

**Última actualización**: 26 de noviembre de 2025  
**Versión de la API**: 1.0.0  
**Contacto**: soporte@ursol-cast-api.com
