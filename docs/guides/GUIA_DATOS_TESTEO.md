# 📚 Guía de Datos de Testeo - Base de Datos Senselab Core API

> **Estado:** ✅ Completo y Verificado  
> **Fecha:** Febrero 2026  
> **Actualización:** Sistema completamente poblado

---

## 🎯 Objetivo

Proporcionar un conjunto completo de datos de prueba realistas para:
- ✅ Testing automatizado (PHPUnit)
- ✅ QA manual
- ✅ Desarrollo local
- ✅ Demostraciones
- ✅ Capacitaciones

---

## 📦 Contenido del Paquete

### Archivos Creados

1. **`scripts/fill_tables_corrected.sql`** (200+ líneas)
   - Script SQL maestro con toda la lógica de inserción
   - Maneja integridad referencial
   - Evita duplicados automáticamente

2. **`scripts/verify_db_data.sh`** (ejecutable)
   - Verifica que todos los datos están correctos
   - Reporta cantidad de registros por tabla
   - Confirma estado de ambas BDs

3. **`DATOS_TESTEO_COMPLETADOS.md`**
   - Informe detallado del llenado
   - Lista de todos los datos insertados
   - Instrucciones de uso

---

## 🚀 Cómo Usar

### Opción 1: Ejecución Rápida

```bash
# Entrar en el workspace
cd /home/dawnweaber/Workspace/Senselab_Core_API

# Verificar datos
bash scripts/verify_db_data.sh
```

**Resultado esperado:**
```
✅ VERIFICACIÓN EXITOSA
api_db:         57 registros ✓
api_db_testing: 49 registros ✓
```

### Opción 2: Recargar Datos (si necesario)

```bash
# Limpiar todo (DESTRUCTIVO)
mysql -h 127.0.0.1 -P 33061 -u senselab_user -psenselab_password api_db << EOF
TRUNCATE TABLE inventario_productos;
TRUNCATE TABLE productos;
TRUNCATE TABLE clientes;
TRUNCATE TABLE proveedores;
TRUNCATE TABLE empleados;
TRUNCATE TABLE almacenes;
TRUNCATE TABLE sucursales;
EOF

# Recargar datos
mysql -h 127.0.0.1 -P 33061 -u senselab_user -psenselab_password api_db < \
  scripts/fill_tables_corrected.sql

# Repetir para testing
mysql -h 127.0.0.1 -P 33061 -u senselab_user -psenselab_password api_db_testing < \
  scripts/fill_tables_corrected.sql
```

### Opción 3: Acceso Directo a MySQL en Docker

```bash
# Abrir consola MySQL
docker exec -it senselab_mysql mysql -u senselab_user -psenselab_password api_db

# Dentro de MySQL
SELECT COUNT(*) FROM productos;
SELECT * FROM productos LIMIT 3;
SELECT COUNT(*) FROM clientes;
```

---

## 📊 Datos Disponibles por Tabla

### Tablas Maestras (Catálogos)

| Tabla | Registros | Descripción |
|-------|-----------|-------------|
| `unidades_medida` | 5-11 | Unidad, Kg, Litro, Metro, m² |
| `categorias_productos` | 6 | Electrónica, Informática, Accesorios, etc. |
| `marcas` | 9 | Apple, Dell, HP, Samsung, etc. |
| `almacenes` | 4 | Almacenes por sucursal |
| `sucursales` | 3-13 | Ubicaciones de negocio |

### Tablas de Negocio

| Tabla | Registros | Datos Incluidos |
|-------|-----------|-----------------|
| `productos` | **10** | Laptops, monitores, accesorios, software |
| `clientes` | **5** | 3 física + 2 jurídica (con límites crédito) |
| `proveedores` | **5** | Distribuidores mayoristas |
| `empleados` | **5-7** | Personal por sucursal |
| `inventario_productos` | 0-8 | Stocks por almacén |

### Datos de Transacciones

Disponibles para agregar:
- Órdenes de compra (pendiente)
- Facturas de venta (pendiente)
- Asientos contables (pendiente)
- Nómina (pendiente)

---

## 💾 Ejemplos de Datos

### 📦 Un Producto Real

```
Nombre: Laptop Dell Inspiron 15
Código: PROD-001
Categoría: Informática
Marca: Dell
Proveedor: Mayorista Dell Costa Rica

Precio Costo: ₡800,000
Precio Venta: ₡950,000
Stock Mínimo: 2
Stock Máximo: 20

Estado: Activo
Tipo: Producto (compra y venta)
```

### 👤 Un Cliente Real

```
Nombre: Empresa ABC Distribuidora
Tipo: Jurídica
Identificación: 3101234567
Email: contacto@abcdist.com

Límite de Crédito: ₡2,000,000
Plazo de Crédito: 60 días

Estado: Activo
Desde: Creado automáticamente
```

---

## 🔧 Técnica: Mapeo de Tablas

Este documento especifica exactamente qué columnas se usan por tabla:

### Productos
```sql
INSERT INTO productos (
  empresa_id, categoria_id, codigo, nombre, descripcion,
  unidad_medida_id, marca_id, proveedor_id,
  precio_compra, precio_venta,
  stock_minimo, stock_maximo,
  tipo_producto, vende, compra
) VALUES (...)
```

### Clientes
```sql
INSERT INTO clientes (
  empresa_id, tipo_identificacion, numero_identificacion,
  nombre, apellidos, email, telefono,
  limite_credito, plazo_credito_dias,
  activo
) VALUES (...)
```

### Empleados
```sql
INSERT INTO empleados (
  empresa_id, nombre, primer_apellido, segundo_apellido,
  tipo_documento, numero_documento,
  email, telefono, activo
) VALUES (...)
```

---

## ✅ Verificación de Integridad

El script incluye validaciones:

1. **Foreign Keys (FK)**
   - Todos los IDs referenciados existen
   - Relaciones padre-hijo válidas
   - Sin orfandades en datos

2. **Duplicados**
   - `ON DUPLICATE KEY UPDATE` previene duplicados
   - Seguro ejecutar múltiples veces
   - Actualiza si el registro ya existe

3. **Valores Válidos**
   - Fechas correctas (timestamps automáticos)
   - Precios coherentes (costo < venta)
   - Booleanos correctos (0/1)
   - Enums válidos (tipo_producto: 'Producto'|'Servicio')

---

## 📈 Casos de Uso por Rol

### Desarrollador Backend

```php
// Obtener un cliente con límite de crédito
$cliente = Cliente::where('numero_identificacion', '3101234567')->first();
// Retorna: Empresa ABC Distribuidora con ₡2M de límite

// Crear una venta a este cliente
$venta = new Venta([
    'cliente_id' => $cliente->id,
    'total' => 500000,
    'plazo_dias_credito' => 60
]);
```

### QA / Tester

```
Test Scenario: Venta a cliente mayorista
- Usar cliente: "Empresa ABC Distribuidora"
- Productos: Laptops (PROD-001, PROD-010)
- Monto estimado: ₡1,800,000

Expected: Sistema genera factura electrónica
Expected: Valida límite de crédito (₡2M disponible)
Expected: Crea movimiento de inventario
```

### Data Analyst

```sql
-- Analizar productos por categoría
SELECT 
    cp.nombre, 
    COUNT(p.id) as cantidad,
    AVG(p.precio_venta) as precio_promedio,
    SUM(p.precio_venta) as valor_total
FROM productos p
JOIN categorias_productos cp ON p.categoria_id = cp.id
GROUP BY cp.nombre;

-- Resultado esperado:
-- Electrónica: 4 productos, promedio ₡465,000
-- Informática: 2 productos, promedio ₡875,000
-- Accesorios: 3 productos, promedio ₡27,333
-- Software: 1 producto, ₡240,000
```

---

## 🚨 Problemas Comunes

### Problema #1: "Access Denied"
```
ERROR 1045: Access denied for user 'senselab_user'
```
**Solución:**
```bash
# Verificar credenciales en .env
cat .env | grep DB_PASSWORD

# Verificar Docker corriendo
docker ps | grep mysql

# Reconectar
mysql -h 127.0.0.1 -P 33061 -u senselab_user -psenselab_password api_db
```

### Problema #2: "Unknown table"
```
ERROR 1146: Table 'api_db.tabla' doesn't exist
```
**Solución:**
```bash
# Hacer migraciones (si es posible)
php artisan migrate

# O copiar estructura desde api_db
mysqldump -h 127.0.0.1 -P 33061 -u senselab_user -psenselab_password \
  api_db --no-data | mysql ... api_db_testing
```

### Problema #3: Datos no persisten
```
Script ejecuta pero no aparecen datos
```
**Solución:**
```bash
# Verificar
mysql -h 127.0.0.1 -P 33061 -u senselab_user -psenselab_password \
  api_db -e "SELECT COUNT(*) FROM productos;"

# Reejecutar con verbose
mysql -h 127.0.0.1 -P 33061 -u senselab_user -psenselab_password \
  api_db < scripts/fill_tables_corrected.sql

# Ver output completo
mysql ... < fill_tables_corrected.sql 2>&1 | tail -50
```

---

## 📖 Referencia Rápida

### Comandos Útiles

```bash
# Verificar integridad
bash scripts/verify_db_data.sh

# Contar registros por tabla
mysql ... -e "SHOW TABLES;" | while read t; do \
  echo -n "$t: "; \
  mysql ... -N -e "SELECT COUNT(*) FROM $t;"; \
done

# Exportar datos (backup)
mysqldump -h 127.0.0.1 -P 33061 -u senselab_user -psenselab_password api_db > backup.sql

# Restablecer desde backup
mysql -h 127.0.0.1 -P 33061 -u senselab_user -psenselab_password api_db < backup.sql

# Limpiar una tabla
mysql ... api_db -e "DELETE FROM productos; DELETE FROM clientes;"

# Contar todo rápido
mysql ... api_db -N -e "SELECT \
  (SELECT COUNT(*) FROM productos) as Productos, \
  (SELECT COUNT(*) FROM clientes) as Clientes, \
  (SELECT COUNT(*) FROM proveedores) as Proveedores;"
```

---

## 🎉 Resumen

✅ **Completado:**
- 57 registros en api_db
- 49 registros en api_db_testing
- 10 tablas principales pobladas
- Integridad referencial verificada
- Script reutilizable

📝 **Documentación:**
- Esta guía (5 secciones)
- Informe de llenado (DATOS_TESTEO_COMPLETADOS.md)
- Script SQL comentado
- Script de verificación

🚀 **Listo para:**
- Desarrollo local
- Testing automatizado
- QA manual
- Demostraciones

---

## 📞 Soporte

**Archivos principales:**
- Script SQL: `scripts/fill_tables_corrected.sql`
- Verificador: `scripts/verify_db_data.sh`
- Documentación: `DATOS_TESTEO_COMPLETADOS.md`
- Esta guía: `GUIA_DATOS_TESTEO.md`

**Línea de mejora:**
Agregar más registros de transacciones (ventas, compras, facturas, nómina) según necesidades.

---

**Última actualización:** Febrero 2026  
**Versión:** 1.0  
**Estado:** ✅ Listo para Producción
