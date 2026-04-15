# Flujo de Facturación Electrónica — Hacienda v4.4

> Proceso completo desde el envío de datos del cliente hasta la aceptación/rechazo por Hacienda de Costa Rica (DGT-R-000-2024).

## Diagrama de Secuencia

```mermaid
sequenceDiagram
    autonumber
    participant C as 👤 Cliente API
    participant API as ⚙️ Laravel Controller
    participant VAL as 🛡️ StoreRequest<br/>Validation
    participant SVC as 📋 HaciendaIntegration<br/>Service
    participant XML as 🏗️ XmlComprobante<br/>Builder v4.4
    participant SIGN as 🔐 XAdES-EPES<br/>Signer
    participant CLAVE as 🔢 ClaveNumerica<br/>Generator
    participant OAUTH as 🔑 OAuthToken<br/>Manager
    participant RL as ⏱️ RateLimiter<br/>30 req/min
    participant HAC as 🏛️ Hacienda API<br/>DGT Costa Rica
    participant DB as 🗄️ MySQL Tenant DB
    participant Q as 📨 Queue Redis/Horizon

    C->>API: POST /api/v1/facturas-electronicas
    API->>VAL: Validate payload
    Note over VAL: condicion_venta (01-15)<br/>receptor_tipo_id (01-06)<br/>medios_pago (01-07+99)<br/>codigos_comerciales (max 5)<br/>detalle_surtido (max 20)
    VAL-->>API: ✅ Validated DTO

    API->>SVC: emitirComprobante(DTO)
    
    SVC->>CLAVE: generateClave()
    Note over CLAVE: 50 dígitos DGT<br/>País + Fecha + Emisor<br/>+ Consecutivo + Random
    CLAVE-->>SVC: clave_numerica

    SVC->>XML: buildXML(data, clave)
    Note over XML: ProveedorSistemas<br/>Emisor (hasta 4 emails)<br/>Receptor<br/>DetalleServicio<br/>OtrosCargos<br/>InformacionReferencia<br/>BaseImponible auto-calc<br/>Exonerados / MedioPago
    XML-->>SVC: xml_unsigned

    SVC->>SIGN: firmar(xml, cert.p12, PIN)
    Note over SIGN: Certificado .p12<br/>XAdES-EPES Policy<br/>SHA256 Hash<br/>DGT-R-000-2024
    SIGN-->>SVC: xml_signed

    SVC->>DB: Save ComprobanteElectronicoFe<br/>estado = 'enviando'
    
    SVC->>Q: Dispatch EnviarHaciendaJob
    
    Q->>OAUTH: getToken()
    Note over OAUTH: OAuth 2.0 Password Grant<br/>Cache token + refresh<br/>api-stag / api-prod
    OAUTH-->>Q: Bearer token

    Q->>RL: checkLimit()
    RL-->>Q: ✅ Under 30/min

    Q->>HAC: POST /recepcion<br/>Authorization: Bearer<br/>Body: XML firmado + Clave
    
    alt ✅ Aceptado (código 202)
        HAC-->>Q: 202 Accepted + clave_hacienda
        Q->>DB: Update estado = 'aceptado'<br/>xml_respuesta, fecha_aceptacion
        Q->>C: 📧 Webhook / Notification
    else ❌ Rechazado
        HAC-->>Q: 400/500 + detalle_error
        Q->>DB: Update estado = 'rechazado'<br/>mensaje_hacienda
        Q->>C: 📧 Webhook error + retry info
    else ⏳ En Proceso
        HAC-->>Q: 202 Processing
        Q->>DB: Update estado = 'procesando'
        Note over Q: Retry exponencial<br/>3 intentos máximo
    end
```

## Componentes del Flujo

| Paso | Servicio | Descripción |
|------|----------|-------------|
| 1-3 | **StoreRequest** | Validación de 20+ campos según normativa DGT v4.4 |
| 4-5 | **ClaveNumericaGenerator** | Genera clave única de 50 dígitos (país+fecha+emisor+consecutivo+random) |
| 6-7 | **XmlComprobanteBuilder** | Construye XML v4.4 con campos: ProveedorSistemas, OtrosCargos, BaseImponible auto-calc, Exonerados, hasta 5 CodigosComerciales, hasta 20 DetallesSurtido |
| 8-9 | **XAdES-EPES Signer** | Firma digital con certificado .p12, política SHA256 según DGT-R-000-2024 |
| 10 | **DB Save** | Persiste ComprobanteElectronicoFe con estado 'enviando' |
| 11-17 | **Job Async** | OAuth token → Rate limit check → POST a Hacienda → Update estado |

## Compliance Hacienda v4.4

- **38/38 brechas resueltas** (100% compliance)
- **49 tests unitarios** del XML Builder (124 assertions)
- **10 tests E2E** contra sandbox real de Hacienda
- **117+ tests totales** del módulo de facturación
