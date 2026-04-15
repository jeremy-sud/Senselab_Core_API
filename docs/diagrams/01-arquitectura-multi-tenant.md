# Arquitectura Multi-Tenant — Ursol CAST API

> Visualización de la separación de datos entre el Landlord (base de datos central) y los Tenants (base de datos por empresa).

## Diagrama de Arquitectura

```mermaid
graph TB
    subgraph Internet["🌐 Internet"]
        CLIENT["👤 Cliente API<br/>Header: X-Empresa-Id"]
    end

    subgraph LB["🔀 Load Balancer / Nginx"]
        NGINX["Nginx Reverse Proxy<br/>SSL Termination"]
    end

    subgraph APP["⚙️ Laravel 12 Application"]
        MW["Middleware Stack<br/>• Sanctum Auth<br/>• TenantIdentification<br/>• CheckPermission<br/>• ETag / RateLimit"]
        
        subgraph IDENT["🔑 Tenant Identification"]
            H1["Header: X-Empresa-Id"]
            H2["Header: X-Tenant-Id"]
            H3["Subdomain Detection"]
        end
        
        MW --> IDENT
        IDENT --> MTA["MakeTenantCurrentAction<br/>Sets Empresa context"]
        MTA --> CTRL["96 Controllers<br/>+ 80 Policies — RBAC"]
        CTRL --> SVC["67 Services<br/>• 10 AI Services<br/>• 9 Hacienda Services<br/>• BaseService CRUD"]
    end

    subgraph DATA["🗄️ Data Layer"]
        subgraph LANDLORD["🏛️ Landlord DB — Central"]
            LDB[("MySQL Master<br/>───────────<br/>usuarios<br/>roles<br/>permisos<br/>empresas<br/>configuracion_api<br/>audit_logs")]
        end

        subgraph TENANT1["🏢 Tenant DB — Empresa A"]
            T1DB[("MySQL<br/>───────────<br/>ventas • compras<br/>inventario<br/>contabilidad<br/>nomina<br/>cuentas_cobrar")]
        end

        subgraph TENANT2["🏢 Tenant DB — Empresa B"]
            T2DB[("MySQL<br/>───────────<br/>ventas • compras<br/>inventario<br/>contabilidad<br/>nomina<br/>cuentas_cobrar")]
        end

        subgraph TENANTN["🏢 Tenant DB — Empresa N"]
            TNDB[("MySQL<br/>───────────<br/>Aislamiento<br/>completo<br/>de datos")]
        end
    end

    subgraph CACHE["⚡ Cache Layer"]
        REDIS[("Redis<br/>───────────<br/>Permisos 1h TTL<br/>Catálogos 1-7d<br/>Sessions<br/>Queues")]
    end

    subgraph REPLICAS["📖 Read Replicas"]
        RR1[("Replica 1<br/>Reports")]
        RR2[("Replica 2<br/>Dashboard")]
    end

    CLIENT --> NGINX
    NGINX --> MW
    SVC --> LDB
    SVC --> T1DB
    SVC --> T2DB
    SVC --> TNDB
    SVC --> REDIS
    SVC --> RR1
    SVC --> RR2

    style LANDLORD fill:#1a1a2e,stroke:#e94560,color:#fff
    style TENANT1 fill:#16213e,stroke:#0f3460,color:#fff
    style TENANT2 fill:#16213e,stroke:#0f3460,color:#fff
    style TENANTN fill:#16213e,stroke:#0f3460,color:#fff
    style CACHE fill:#0d1b2a,stroke:#ffc107,color:#fff
    style APP fill:#1b2838,stroke:#66c0f4,color:#fff
```

## Conceptos Clave

| Concepto | Descripción |
|----------|-------------|
| **Landlord DB** | Base de datos compartida: `usuarios`, `roles`, `permisos`, `empresas`, `configuracion_api` |
| **Tenant DB** | Base de datos aislada por empresa: `ventas`, `compras`, `inventario`, `contabilidad`, `nomina` |
| **Identificación** | Vía headers `X-Empresa-Id`, `X-Tenant-Id` o subdominio |
| **BelongsToTenant** | Trait que agrega `where empresa_id = $tenantId` automático a todos los queries |
| **MakeTenantCurrentAction** | Acción de Spatie que establece el contexto del tenant en cada request/job |
| **Queues Tenant-Aware** | Todos los jobs heredan automáticamente el contexto del tenant |

## Tablas por Capa

### 🏛️ Landlord (Compartida)
`usuarios`, `roles`, `permisos`, `roles_permisos`, `empresas`, `configuracion_api`, `audit_logs`, `webhooks`, `webhook_logs`

### 🏢 Tenant (Por Empresa) — 43+ modelos
`ventas`, `detalle_ventas`, `clientes`, `productos`, `compras`, `ordenes_compra`, `inventario_productos`, `almacenes`, `cuentas_contables`, `asientos_contables`, `detalle_asientos`, `empleados`, `nominas`, `cuentas_cobrar`, `cuentas_pagar`, `comprobantes_electronicos_fe`, `rutas`, `horarios_ruta`, `bus_unidades`, y más.
