# 🤖 Funcionalidades de Inteligencia Artificial - Ursol CAST API

**Última Actualización:** 5 de Diciembre 2025

## 🎉 ¡AHORA CON IA GRATUITA!

El sistema utiliza **Google Gemini API** por defecto, que es **completamente gratuito**.

| Característica | Gemini (Gratis) | OpenAI (Pago) |
|----------------|-----------------|---------------|
| **Costo** | $0.00 | ~$0.01-0.05/request |
| **Chat/Texto** | ✅ gemini-2.0-flash | gpt-4 |
| **Vision/OCR** | ✅ gemini-1.5-flash | gpt-4-vision |
| **Límite** | 15 req/min, 1500/día | Según plan |
| **Obtener Key** | [aistudio.google.com](https://aistudio.google.com/) | platform.openai.com |

## 📊 Resumen Ejecutivo

Se han implementado **10 servicios de IA** y **32 endpoints API** que potencian las capacidades del ERP:

### Servicios Implementados (10 total)

| Servicio | Archivo | Descripción |
|----------|---------|-------------|
| `GeminiService` | `app/Services/AI/GeminiService.php` | Integración principal con Google Gemini (GRATUITO) |
| `OpenAIService` | `app/Services/AI/OpenAIService.php` | Fallback con OpenAI (de pago) |
| `OCRService` | `app/Services/AI/OCRService.php` | Escaneo de facturas con visión por computadora |
| `ChatbotService` | `app/Services/AI/ChatbotService.php` | Asistente virtual con RAG y detección de intenciones |
| `PredictionService` | `app/Services/AI/PredictionService.php` | Predicciones de demanda e inventario |
| `AnomalyDetectionService` | `app/Services/AI/AnomalyDetectionService.php` | Detección de fraudes y anomalías |
| `ContentGeneratorService` | `app/Services/AI/ContentGeneratorService.php` | Generación de emails, reportes, notificaciones |
| `CabysClassifierService` | `app/Services/AI/CabysClassifierService.php` | Clasificación tributaria CABYS (Costa Rica) |
| `CreditScoringService` | `app/Services/AI/CreditScoringService.php` | Scoring crediticio de clientes (0-100) |
| `AIServiceInterface` | `app/Services/AI/AIServiceInterface.php` | Interfaz base para todos los servicios |

### Alta Prioridad (Impacto Inmediato) ✅

| Módulo | Descripción | Endpoints | Tecnología |
|--------|-------------|-----------|------------|
| **OCR Facturas** | Escaneo automático de facturas de proveedores | 3 | Gemini Vision |
| **Chatbot ERP** | Asistente virtual con consultas en lenguaje natural | 3 | Gemini 2.0 Flash |
| **Predicciones** | Análisis de demanda y alertas de inventario | 6 | Análisis estadístico + IA |

### Prioridad Media (Valor Agregado) ✅

| Módulo | Descripción | Endpoints | Tecnología |
|--------|-------------|-----------|------------|
| **Detección de Anomalías** | Detecta fraudes, errores contables y transacciones sospechosas | 4 | Análisis estadístico + IA |
| **Generación de Contenido** | Emails de cobro, agradecimiento, reportes automáticos | 5 | Gemini 2.0 Flash |
| **Clasificación CABYS** | Sugiere códigos CABYS para productos (Costa Rica) | 5 | Keywords + IA |
| **Credit Scoring** | Calcula riesgo crediticio de clientes (0-100) | 6 | Scoring ponderado + IA |

---

## 📁 Archivos del Sistema

### Configuración
- `config/gemini.php` - Configuración de Google Gemini (GRATUITO)
- `config/openai.php` - Configuración de OpenAI (alternativa de pago)

### Servicios (`app/Services/AI/`) - 10 archivos
- `AIServiceInterface.php` - Interfaz base para servicios de IA
- `GeminiService.php` - **Integración con Google Gemini (GRATUITO)**
- `OpenAIService.php` - Integración con OpenAI (alternativa de pago)
- `OCRService.php` - Procesamiento de facturas con visión por computadora
- `ChatbotService.php` - Chatbot con detección de intenciones y RAG
- `PredictionService.php` - Predicciones de demanda e inventario
- `AnomalyDetectionService.php` - Detección de anomalías y fraudes
- `ContentGeneratorService.php` - Generación de contenido automático
- `CabysClassifierService.php` - Clasificación de códigos CABYS
- `CreditScoringService.php` - Scoring crediticio de clientes

### Controladores (`app/Http/Controllers/Api/V1/AI/`) - 4 archivos
- `AnomalyController.php` - Endpoints para detección de anomalías
- `ContentController.php` - Endpoints para generación de contenido
- `CabysController.php` - Endpoints para clasificación CABYS
- `CreditController.php` - Endpoints para credit scoring

### Rutas
- `routes/api.php` - **32 endpoints** bajo `/api/ai/`

---

## 🔧 Configuración Inicial

### 1. Obtener API Key GRATUITA de Google Gemini

1. Ir a https://aistudio.google.com/
2. Iniciar sesión con tu cuenta de Google
3. Hacer clic en "Get API Key" → "Create API key"
4. Copiar la API key generada

### 2. Variables de Entorno

Agregar al archivo `.env`:

```env
# Google Gemini (GRATUITO - Recomendado)
GEMINI_API_KEY=tu-api-key-aqui

# Modelos (opcional, tiene defaults)
GEMINI_CHAT_MODEL=gemini-2.0-flash
GEMINI_VISION_MODEL=gemini-1.5-flash
```

### 3. Alternativa: OpenAI (De pago)

Si prefieres usar OpenAI en lugar de Gemini:

```env
# OpenAI (Solo si no usas Gemini)
OPENAI_API_KEY=sk-tu-api-key
```

**Nota:** El sistema usa Gemini automáticamente si `GEMINI_API_KEY` está configurado.

---

## 📡 Endpoints de API

### OCR - Escaneo de Facturas

#### `POST /api/ai/ocr/invoice`
Escanea una factura y extrae datos automáticamente.

**Request (multipart/form-data):**
```
file: [archivo PDF, JPG o PNG]
options[match_proveedor]: true
```

**Response:**
```json
{
    "success": true,
    "datos_generales": {
        "numero_factura": "FAC-001234",
        "fecha_emision": "2025-01-15",
        "proveedor": {
            "nombre": "Distribuidora XYZ",
            "cedula": "3-101-123456",
            "telefono": "2222-3333"
        },
        "subtotal": 85000.00,
        "impuestos": 11050.00,
        "total": 96050.00,
        "moneda": "CRC"
    },
    "productos": [
        {
            "descripcion": "Producto ABC",
            "cantidad": 10,
            "unidad": "unidad",
            "precio_unitario": 8500.00,
            "subtotal": 85000.00
        }
    ],
    "proveedor_match": {
        "found": true,
        "id": 123,
        "nombre": "Distribuidora XYZ S.A.",
        "confidence": 0.95
    },
    "confianza_general": 0.92
}
```

#### `POST /api/ai/ocr/batch`
Procesa múltiples facturas en lote (máx. 10).

#### `GET /api/ai/ocr/capabilities`
Retorna formatos soportados y límites del servicio.

---

### Chatbot - Asistente Virtual ERP

#### `POST /api/ai/chat`
Envía un mensaje al asistente virtual.

**Request:**
```json
{
    "message": "¿Cuánto vendimos el mes pasado?",
    "session_id": "optional-session-id"
}
```

**Response:**
```json
{
    "success": true,
    "response": "El mes pasado las ventas totales fueron de ₡2,450,000 colones...",
    "intent": "ventas",
    "data": {
        "total_ventas": 2450000,
        "num_transacciones": 156,
        "ticket_promedio": 15705.13
    },
    "session_id": "abc123",
    "suggestions": [
        "¿Cuál fue el producto más vendido?",
        "Comparar con el mes anterior"
    ]
}
```

**Intenciones Soportadas:**
- `ventas` - Consultas sobre ventas y facturación
- `inventario` - Stock, alertas, movimientos
- `clientes` - Información de clientes, cuentas por cobrar
- `facturas` - Facturas electrónicas, estados
- `reportes` - Reportes y estadísticas
- `cuentas_cobrar` - Cuentas por cobrar, saldos pendientes

#### `GET /api/ai/chat/suggestions`
Obtiene preguntas sugeridas basadas en el contexto.

#### `DELETE /api/ai/chat/history`
Limpia el historial de conversación.

---

### Predicciones - Demanda e Inventario

#### `GET /api/ai/predictions/product/{productoId}`
Predicción detallada para un producto específico.

**Query params:**
- `days` - Días a predecir (1-365, default: 30)

**Response:**
```json
{
    "success": true,
    "producto": {
        "id": 123,
        "codigo": "PROD-001",
        "nombre": "Producto Ejemplo"
    },
    "stock_actual": 150,
    "estadisticas": {
        "promedio_diario": 5.2,
        "promedio_semanal": 36.4,
        "tendencia": "creciente"
    },
    "prediccion": {
        "dias": 30,
        "demanda_estimada": 156,
        "rango_minimo": 130,
        "rango_maximo": 182,
        "confianza": "alta"
    },
    "inventario": {
        "dias_stock_restante": 28.8,
        "punto_reorden": 45,
        "cantidad_sugerida_compra": 100,
        "fecha_estimada_agotamiento": "2025-02-12"
    },
    "alerta": {
        "nivel": "medio",
        "mensaje": "Stock moderado. Planificar reabastecimiento.",
        "accion_recomendada": "Incluir en próxima orden de compra"
    }
}
```

#### `GET /api/ai/predictions/alerts`
Lista productos con bajo stock ordenados por urgencia.

**Query params:**
- `limit` - Número máximo de alertas (1-100, default: 20)

**Response:**
```json
{
    "success": true,
    "total_alertas": 15,
    "resumen": {
        "critico": 3,
        "alto": 5,
        "medio": 4,
        "bajo": 3
    },
    "alertas": [
        {
            "producto_id": 45,
            "codigo": "PROD-045",
            "nombre": "Producto Crítico",
            "stock_actual": 2,
            "dias_stock": 0.4,
            "nivel_alerta": "critico",
            "mensaje": "Stock para 0.4 días",
            "cantidad_sugerida": 150
        }
    ]
}
```

#### `GET /api/ai/predictions/recommendations`
Recomendaciones de compra priorizadas con IA.

#### `GET /api/ai/predictions/trends`
Análisis de tendencias de ventas por categoría.

**Query params:**
- `months` - Meses de historial (1-24, default: 6)

#### `GET /api/ai/predictions/revenue`
Predicción de ingresos para el próximo período.

**Query params:**
- `days` - Días a predecir (1-365, default: 30)

#### `GET /api/ai/predictions/dashboard`
Dashboard ejecutivo con todas las métricas de predicción.

---

## 🏗️ Arquitectura

```
┌─────────────────────────────────────────────────────────┐
│                     API Layer                            │
│  ┌──────────────┐ ┌──────────────┐ ┌────────────────┐   │
│  │ OCRController│ │ChatController│ │PredictControlle│   │
│  └──────┬───────┘ └──────┬───────┘ └───────┬────────┘   │
└─────────┼────────────────┼─────────────────┼────────────┘
          │                │                 │
┌─────────▼────────────────▼─────────────────▼────────────┐
│                    Service Layer                         │
│  ┌──────────────┐ ┌──────────────┐ ┌────────────────┐   │
│  │  OCRService  │ │ChatbotService│ │PredictionService│  │
│  └──────┬───────┘ └──────┬───────┘ └───────┬────────┘   │
└─────────┼────────────────┼─────────────────┼────────────┘
          │                │                 │
          └────────┬───────┴─────────────────┘
                   │
┌──────────────────▼──────────────────────────────────────┐
│                    OpenAI Service                        │
│  ┌────────────────────────────────────────────────────┐ │
│  │ • Chat Completions (GPT-4)                         │ │
│  │ • Vision Analysis (GPT-4 Vision)                   │ │
│  │ • Embeddings (text-embedding-3-small)              │ │
│  │ • Function Calling                                 │ │
│  └────────────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────────────┘
```

---

## 🔐 Seguridad

- Todos los endpoints requieren autenticación (`auth:sanctum`)
- Rate limiting específico por endpoint:
  - OCR: 30 req/min (single), 10 req/min (batch)
  - Chat: 60 req/min
  - Predictions: 120 req/min (default)
- Multi-tenancy automático (filtrado por `empresa_id`)
- Logs de uso para auditoría

---

## 💰 Costos

| Proveedor | Operación | Costo |
|-----------|-----------|-------|
| **Gemini** | Todas las operaciones | **$0.00 (GRATIS)** |
| OpenAI | Chat (1 mensaje) | $0.01-0.03 |
| OpenAI | OCR factura | $0.02-0.05 |

**El sistema usa Gemini por defecto = Sin costos**

---

## 🧪 Testing

Los tests existentes (405) siguen pasando. Para probar manualmente:

```bash
# Verificar rutas registradas
docker exec ursol_php php artisan route:list --path=ai

# Probar endpoint de capabilities (no requiere OpenAI key)
curl -X GET http://localhost:8000/api/ai/ocr/capabilities \
  -H "Authorization: Bearer {token}"

# Probar chat
curl -X POST http://localhost:8000/api/ai/chat \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{"message": "¿Cuánto vendimos hoy?"}'

# Probar alertas de stock
curl -X GET http://localhost:8000/api/ai/predictions/alerts \
  -H "Authorization: Bearer {token}"
```

---

## ✅ PRIORIDAD MEDIA - Valor Agregado (COMPLETADO)

Se han implementado **4 módulos adicionales de IA** de prioridad media:

| Módulo | Descripción | Endpoints |
|--------|-------------|-----------|
| **Detección de Anomalías** | Detecta fraudes, errores contables, transacciones sospechosas | 4 endpoints |
| **Generación de Contenido** | Emails de cobro, agradecimiento, reportes con IA | 5 endpoints |
| **Clasificación CABYS** | Sugiere códigos CABYS para productos (Costa Rica) | 5 endpoints |
| **Credit Scoring** | Calcula riesgo crediticio de clientes (0-100) | 6 endpoints |

### Archivos Creados (Prioridad Media)

**Servicios (`app/Services/AI/`):**
- `AnomalyDetectionService.php` - Detección de anomalías financieras
- `ContentGeneratorService.php` - Generación de contenido con IA
- `CabysClassifierService.php` - Clasificación de códigos CABYS
- `CreditScoringService.php` - Scoring de riesgo crediticio

**Controladores (`app/Http/Controllers/Api/V1/AI/`):**
- `AnomalyController.php` - Endpoints de auditoría
- `ContentController.php` - Endpoints de generación
- `CabysController.php` - Endpoints de clasificación
- `CreditController.php` - Endpoints de scoring

---

### 🔍 Detección de Anomalías

#### `POST /api/ai/anomalies/sales`
Detecta anomalías en ventas (transacciones atípicas).

#### `POST /api/ai/anomalies/cash-flow`
Detecta anomalías en flujo de caja.

#### `POST /api/ai/anomalies/accounting`
Detecta errores contables y descuadres.

#### `POST /api/ai/anomalies/audit`
Ejecuta auditoría completa con análisis de IA.

**Request:**
```json
{
    "start_date": "2024-01-01",
    "end_date": "2024-12-31",
    "include_ai_analysis": true
}
```

---

### ✉️ Generación de Contenido

#### `POST /api/ai/content/payment-reminder`
Genera email de recordatorio de pago personalizado.

**Request:**
```json
{
    "cliente_id": 1,
    "factura_ids": [123, 456],
    "tone": "friendly",
    "language": "es"
}
```

#### `POST /api/ai/content/thank-you`
Genera email de agradecimiento.

#### `POST /api/ai/content/invoice-email`
Genera email para envío de factura.

#### `POST /api/ai/content/report`
Genera reporte ejecutivo del negocio.

#### `POST /api/ai/content/custom`
Genera contenido personalizado con prompt libre.

---

### 🏷️ Clasificación CABYS

#### `POST /api/ai/cabys/classify`
Sugiere código CABYS para un producto.

**Request:**
```json
{
    "description": "Laptop Dell Inspiron 15 pulgadas",
    "category_hint": "electrónica",
    "max_suggestions": 5
}
```

**Response:**
```json
{
    "success": true,
    "data": {
        "suggestions": [
            {
                "code": "4321001001",
                "description": "Computadoras portátiles",
                "confidence": 0.95,
                "tax_rate": 13
            }
        ],
        "recommended": "4321001001"
    }
}
```

#### `POST /api/ai/cabys/batch`
Clasifica múltiples productos en lote (máx. 50).

#### `GET /api/ai/cabys/search?query=computadora`
Busca códigos CABYS por descripción.

#### `GET /api/ai/cabys/validate/{code}`
Valida si un código CABYS es válido.

#### `GET /api/ai/cabys/suggest/{productoId}`
Sugiere código óptimo para producto existente.

---

### 💳 Credit Scoring

#### `GET /api/ai/credit/score/{clienteId}`
Calcula score de crédito del cliente (0-100).

**Response:**
```json
{
    "success": true,
    "data": {
        "score": 75,
        "risk_level": "low",
        "risk_color": "green",
        "payment_probability": 0.92,
        "factors": {
            "payment_history": 85,
            "purchase_frequency": 70,
            "average_amount": 65,
            "aging": 80,
            "relationship_length": 90
        }
    }
}
```

#### `GET /api/ai/credit/analysis/{clienteId}`
Análisis detallado con tendencias y recomendaciones.

#### `GET /api/ai/credit/limit/{clienteId}`
Recomienda límite de crédito basado en perfil.

#### `POST /api/ai/credit/batch`
Calcula scores para múltiples clientes (máx. 100).

#### `GET /api/ai/credit/ranking`
Ranking de clientes por score de crédito.

#### `POST /api/ai/credit/evaluate`
Evalúa riesgo de una transacción específica.

---

## 📊 Resumen de Endpoints de IA

| Categoría | Endpoints | Estado |
|-----------|-----------|--------|
| OCR | 3 | ✅ Completado |
| Chatbot | 3 | ✅ Completado |
| Predicciones | 6 | ✅ Completado |
| Anomalías | 4 | ✅ Completado |
| Contenido | 5 | ✅ Completado |
| CABYS | 5 | ✅ Completado |
| Credit Scoring | 6 | ✅ Completado |
| **Total** | **32** | ✅ |

---

## 📋 Próximos Pasos (Prioridad Baja)

1. **Asistencia Contable** - Sugerir asientos contables automáticamente
2. **Reconocimiento de Voz** - Comandos de voz para el ERP
3. **Generación de Imágenes** - Crear thumbnails de productos

---

## 📞 Soporte

Desarrollado por **Sistemas Ursol S.A.**
- Documentación: `/docs/ai`
- Issues: GitHub Issues del repositorio
