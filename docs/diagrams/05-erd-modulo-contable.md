# ERD — Módulo Contable

> Relaciones entre Empresa, Cuentas Contables (jerárquicas), Asientos y sus Detalles.

## Diagrama Entidad-Relación

```mermaid
erDiagram
    Empresa ||--o{ CuentaContable : "posee"
    Empresa ||--o{ AsientoContable : "registra"
    Empresa ||--o{ Sucursal : "tiene"
    
    CuentaContable ||--o{ CuentaContable : "cuenta_padre_id"
    CuentaContable }o--|| TipoCuenta : "tipo"
    CuentaContable ||--o{ DetalleAsiento : "movimientos"
    
    AsientoContable ||--o{ DetalleAsiento : "líneas"
    AsientoContable }o--|| Empresa : "empresa_id"
    
    DetalleAsiento }o--|| AsientoContable : "asiento_contable_id"
    DetalleAsiento }o--|| CuentaContable : "cuenta_contable_id"
    
    Venta ||--o| AsientoContable : "genera"
    
    Empresa {
        bigint id PK
        string nombre
        string cedula_juridica
        string tipo_identificacion
        boolean activo
        timestamp creado_en
        timestamp actualizado_en
    }
    
    CuentaContable {
        bigint id PK
        bigint empresa_id FK
        bigint tipo_cuenta_id FK
        bigint cuenta_padre_id FK
        string codigo
        string nombre
        string descripcion
        boolean acepta_movimientos
        boolean activo
    }
    
    TipoCuenta {
        bigint id PK
        string nombre
        string naturaleza
        int orden
    }
    
    AsientoContable {
        bigint id PK
        bigint empresa_id FK
        int numero_asiento
        date fecha_asiento
        string descripcion
        decimal total_debe
        decimal total_haber
        string estado
        boolean activo
    }
    
    DetalleAsiento {
        bigint id PK
        bigint asiento_contable_id FK
        bigint cuenta_contable_id FK
        decimal monto_debe
        decimal monto_haber
        string descripcion
    }
    
    Venta {
        bigint id PK
        bigint empresa_id FK
        bigint cliente_id FK
        string estado_venta
        decimal total
    }
```

## Notas de Diseño

- **CuentaContable** tiene relación **auto-referencial** via `cuenta_padre_id` para soportar el árbol jerárquico (ej: Activo → Circulante → Bancos → Banco X)
- **AsientoContable** valida cuadre: `total_debe == total_haber` mediante `estaCuadrado()`
- **DetalleAsiento** es la tabla pivote que conecta cada línea del asiento con su cuenta contable
- **Venta** genera automáticamente un AsientoContable al completarse
- Todos los modelos usan `BelongsToTenant` → filtro automático por `empresa_id`
