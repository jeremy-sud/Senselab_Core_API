# ERD — Módulo Logística / Transporte

> Relaciones entre Rutas, Horarios, Buses y Venta de Boletos.

## Diagrama Entidad-Relación

```mermaid
erDiagram
    Empresa ||--o{ Ruta : "gestiona"
    Empresa ||--o{ BusUnidad : "posee"
    Empresa ||--o{ Sucursal : "opera en"
    
    Ruta ||--o{ HorarioRuta : "programada en"
    BusUnidad ||--o{ HorarioRuta : "asignado a"
    
    HorarioRuta ||--o{ Venta : "genera boletos"
    Venta }o--|| Cliente : "comprado por"
    Venta ||--o{ DetalleVenta : "contiene"
    
    BusUnidad }o--|| ModeloBus : "modelo"
    
    Empresa {
        bigint id PK
        string nombre
        string cedula_juridica
        boolean activo
    }
    
    Ruta {
        bigint id PK
        bigint empresa_id FK
        string nombre
        string origen
        string destino
        decimal distancia_km
        string duracion_estimada
        decimal tarifa_base
        boolean activo
    }
    
    BusUnidad {
        bigint id PK
        bigint empresa_id FK
        bigint modelo_bus_id FK
        string placa
        int capacidad_pasajeros
        string estado
        boolean activo
    }
    
    ModeloBus {
        bigint id PK
        string marca
        string modelo
        int anio
        int capacidad
    }
    
    HorarioRuta {
        bigint id PK
        bigint ruta_id FK
        bigint bus_id FK
        date fecha_salida
        string hora_salida
        date fecha_llegada_estimada
        string hora_llegada_estimada
        int asientos_disponibles
        string estado
    }
    
    Venta {
        bigint id PK
        bigint empresa_id FK
        bigint cliente_id FK
        decimal total
        string estado_venta
    }
    
    Cliente {
        bigint id PK
        string nombre
        string identificacion
        string email
    }
    
    DetalleVenta {
        bigint id PK
        bigint venta_id FK
        bigint producto_id FK
        int cantidad
        decimal precio_unitario
        decimal subtotal
    }
```

## Flujo Operativo

```
1. 🛣️ Empresa define Rutas (origen → destino, tarifa, duración)
2. 🚌 Empresa registra BusUnidades (placa, capacidad, modelo)
3. 📅 Se crean HorariosRuta asignando Bus + Ruta + Fecha/Hora
4. 🎫 Cliente compra Boleto → genera Venta con DetalleVenta
5. 📊 asientos_disponibles se decrementa automáticamente
```
