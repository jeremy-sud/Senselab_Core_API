# Ciclo de Vida del Dato con IA — 10 Servicios

> Cómo un archivo físico pasa por OCR, se clasifica automáticamente con CAByS y alimenta el Análisis Financiero y Credit Scoring.

## Diagrama de Flujo Completo

```mermaid
graph LR
    subgraph INPUT["📥 ENTRADA"]
        PDF["📄 Factura<br/>PDF / IMG"]
        TXT["📝 Descripción<br/>Producto"]
        HIST["📊 Datos<br/>Históricos"]
        MSG["💬 Consulta<br/>Usuario"]
    end

    subgraph STAGE1["🔍 ETAPA 1 — Extracción"]
        OCR["🤖 OCRService<br/>───────────<br/>Gemini Vision<br/>92% Precisión<br/>───────────<br/>Extrae:<br/>• Proveedor<br/>• Líneas detalle<br/>• Totales<br/>• Confianza 0-100"]
    end

    subgraph STAGE2["🏷️ ETAPA 2 — Clasificación"]
        CABYS["🏷️ CabysClassifier<br/>───────────<br/>Gemini + CABYS DB<br/>98% Precisión<br/>───────────<br/>12 categorías<br/>10,000+ subcódigos<br/>Código tributario CR"]
    end

    subgraph STAGE3["🧠 ETAPA 3 — Análisis"]
        PRED["🔮 PredictionService<br/>Forecast demanda 30d<br/>Alertas reabastecimiento"]
        ANOM["🚨 AnomalyDetection<br/>Detección fraude 95%<br/>Transacciones mayor a 3σ"]
        CREDIT["💳 CreditScoring<br/>Score 0-100<br/>6 factores ponderados"]
    end

    subgraph STAGE4["💡 ETAPA 4 — Generación"]
        CHAT["💬 ChatbotService<br/>RAG + Intent Detection<br/>Asistente contextual"]
        CONTENT["📝 ContentGenerator<br/>Emails automáticos<br/>Reportes narrativos"]
    end

    subgraph STAGE5["📊 ETAPA 5 — Salida"]
        FE["📋 Factura Electrónica<br/>Datos pre-llenados"]
        INV["📦 Inventario<br/>Alertas stock"]
        DASH["📈 Dashboard KPIs<br/>Métricas tiempo real"]
        REPORT["📄 Reportes<br/>PDF / Excel / CSV"]
        ALERT["🔔 Notificaciones<br/>Webhooks + Email"]
    end

    subgraph PROVIDERS["☁️ Proveedores IA"]
        GEM["🟡 Google Gemini 2.0<br/>GRATUITO — 15 RPM"]
        GPT["🟢 OpenAI GPT-4o<br/>Fallback de pago"]
    end

    PDF --> OCR
    TXT --> CABYS
    HIST --> PRED
    HIST --> ANOM
    HIST --> CREDIT
    MSG --> CHAT

    OCR --> CABYS
    CABYS --> FE
    CABYS --> INV

    PRED --> DASH
    PRED --> INV
    ANOM --> ALERT
    ANOM --> DASH
    CREDIT --> REPORT
    CREDIT --> DASH

    CHAT --> REPORT
    CONTENT --> ALERT

    OCR -.-> GEM
    CABYS -.-> GEM
    CHAT -.-> GEM
    CONTENT -.-> GEM
    PRED -.-> GEM
    ANOM -.-> GEM
    CHAT -.-> GPT

    style STAGE1 fill:#1a472a,stroke:#2ecc71,color:#fff
    style STAGE2 fill:#1a3a5c,stroke:#3498db,color:#fff
    style STAGE3 fill:#4a1a5c,stroke:#9b59b6,color:#fff
    style STAGE4 fill:#5c3a1a,stroke:#e67e22,color:#fff
    style STAGE5 fill:#1a1a5c,stroke:#e74c3c,color:#fff
```

## 10 Servicios de IA — Detalle

| # | Servicio | Provider | Endpoints | Precisión | Descripción |
|---|----------|----------|-----------|-----------|-------------|
| 1 | **GeminiService** | Google Gemini 2.0 Flash | LLM genérico | — | Motor principal, **GRATUITO** |
| 2 | **OpenAIService** | GPT-4o | Fallback + embeddings | — | Alternativa de pago |
| 3 | **OCRService** | Gemini Vision | 3 endpoints | **92%** | Escaneo inteligente de facturas |
| 4 | **ChatbotService** | Gemini 2.0 Flash | 3 endpoints | **90%** | Asistente RAG contextual |
| 5 | **PredictionService** | Gemini + estadística | 6 endpoints | **85%** | Forecast de demanda 30 días |
| 6 | **AnomalyDetectionService** | Gemini + heurísticas | 4 endpoints | **95%** | Detección de fraude y anomalías |
| 7 | **ContentGeneratorService** | Gemini 2.0 Flash | 5 endpoints | — | Emails y reportes automáticos |
| 8 | **CabysClassifierService** | Gemini + CABYS DB | 5 endpoints | **98%** | Clasificación tributaria CR |
| 9 | **CreditScoringService** | Modelo matemático | 6 endpoints | **88%** | Score de crédito 0-100 |
| 10 | **AIServiceInterface** | Contrato | — | — | Interface para todos los servicios |

## Ejemplo: Flujo OCR → CAByS → Factura Electrónica

```
1. 📄 Usuario sube factura PDF del proveedor
2. 🤖 OCRService extrae: proveedor, líneas, totales (confianza: 92%)
3. 🏷️ CabysClassifier asigna código tributario a cada línea (confianza: 98%)
4. 📋 Datos pre-llenan formulario de Compra + generan asiento contable
5. ✅ Factura Electrónica lista para envío a Hacienda
```
