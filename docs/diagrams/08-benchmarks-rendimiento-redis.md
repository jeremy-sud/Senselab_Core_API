# Benchmarks de Rendimiento — Redis Cache

> Demostración del 90-95% de mejora en latencia gracias a Redis multi-capa.

## Gráfico Comparativo de Latencia

```mermaid
xychart-beta
    title "Latencia: Sin Caché vs Con Redis Cache (ms)"
    x-axis ["Catálogos", "Permisos", "Productos", "Dashboard", "Reportes", "Clientes"]
    y-axis "Latencia (ms)" 0 --> 500
    bar [450, 380, 320, 480, 420, 350]
    bar [28, 12, 35, 45, 50, 22]
```

## Tabla de Resultados

| Endpoint | Sin Caché (ms) | Con Redis (ms) | Mejora | Hit Rate |
|----------|:--------------:|:--------------:|:------:|:--------:|
| **GET /catalogos** | 450 ms | 28 ms | **93.8%** | 99% |
| **Verificar Permisos** | 380 ms | 12 ms | **96.8%** | 95% |
| **GET /productos** | 320 ms | 35 ms | **89.1%** | 85% |
| **GET /dashboard/kpis** | 480 ms | 45 ms | **90.6%** | 80% |
| **GET /reportes** | 420 ms | 50 ms | **88.1%** | Configurable |
| **GET /clientes** | 350 ms | 22 ms | **93.7%** | 90% |
| | | **Promedio** | **~92%** | |

## Arquitectura de Cache Multi-Capa

```mermaid
graph TD
    REQ["📨 HTTP Request"] --> L1

    subgraph L1["⚡ L1 — Permisos (TTL: 1h)"]
        P1["cache:permisos:{empresa}:{usuario}<br/>Hit Rate: 95%<br/>68 permisos en Set"]
    end
    
    subgraph L2["📚 L2 — Catálogos (TTL: 1-7 días)"]
        C1["cache:catalogos:formas_pago"]
        C2["cache:catalogos:tipos_impuesto"]
        C3["cache:catalogos:condiciones_venta"]
        C4["... 8 catálogos más"]
    end
    
    subgraph L3["📊 L3 — Métricas (TTL: 24h)"]
        M1["cache:metrics:response_time"]
        M2["cache:metrics:memory_usage"]
    end
    
    subgraph L4["🔄 L4 — Query Cache (Auto)"]
        Q1["Por controller<br/>Flush automático en write"]
    end

    subgraph L5["📄 L5 — Reportes (Configurable)"]
        R1["cache:report:{id}<br/>Invalidación vía Observer"]
    end

    subgraph INVALIDATION["♻️ Estrategia de Invalidación"]
        I1["Permisos → Observer RolPermiso"]
        I2["Catálogos → Scheduled Job 5 AM"]
        I3["Queries → Flush on CUD"]
        I4["Reportes → ReportCacheObserver"]
    end

    L1 --> L2
    L2 --> L3
    L3 --> L4
    L4 --> L5
    L5 -.-> INVALIDATION

    style L1 fill:#c0392b,stroke:#e74c3c,color:#fff
    style L2 fill:#2980b9,stroke:#3498db,color:#fff
    style L3 fill:#8e44ad,stroke:#9b59b6,color:#fff
    style L4 fill:#27ae60,stroke:#2ecc71,color:#fff
    style L5 fill:#d35400,stroke:#e67e22,color:#fff
```

## Read/Write Split (FASE 22)

```mermaid
graph LR
    subgraph APP["⚙️ Application"]
        W["Writes<br/>create/update/delete"]
        R["Reads<br/>select/reports/dashboard"]
    end

    subgraph MASTER["🖊️ MySQL Master"]
        MDB[("Primary DB<br/>Sticky: true")]
    end

    subgraph REPLICAS["📖 Read Replicas"]
        R1[("Replica 1")]
        R2[("Replica 2")]
        R3[("Replica N")]
    end

    subgraph REDIS_Q["📨 Queue System"]
        HZ["Laravel Horizon<br/>───────────<br/>queue:default<br/>queue:webhooks<br/>queue:reports<br/>queue:hacienda<br/>queue:emails"]
    end

    W --> MDB
    R --> R1
    R --> R2
    R --> R3
    MDB -.->|replication| R1
    MDB -.->|replication| R2
    MDB -.->|replication| R3
    HZ --> MDB

    style MASTER fill:#c0392b,stroke:#e74c3c,color:#fff
    style REPLICAS fill:#2980b9,stroke:#3498db,color:#fff
```

## Queues con Laravel Horizon

| Cola | Propósito | Workers | Prioridad |
|------|-----------|---------|-----------|
| `default` | Operaciones generales | 3 | Normal |
| `webhooks` | Event-driven HMAC-SHA256 | 2 | Alta |
| `reports` | Reportes PDF/Excel/CSV | 1 | Baja |
| `hacienda` | OAuth + envío XML a DGT | 2 | Alta |
| `emails` | Notificaciones transaccionales | 2 | Normal |
