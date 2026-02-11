-- ================================================
-- SCRIPT PARA LLENAR TABLAS DE PRUEBA - api_db
-- Corregido con mapeo exacto de columnas
-- ================================================

SET FOREIGN_KEY_CHECKS=0;

-- ==========================================
-- 1. UNIDADES DE MEDIDA
-- ==========================================
INSERT INTO unidades_medida (codigo_dgt, nombre, descripcion, activo) VALUES
('01', 'Unidad', 'Unidad individual', 1),
('02', 'Kilogramo', 'Peso en kilogramos', 1),
('03', 'Litro', 'Volumen en litros', 1),
('04', 'Metro', 'Longitud en metros', 1),
('05', 'Metro cuadrado', 'Área en metros cuadrados', 1)
ON DUPLICATE KEY UPDATE nombre = VALUES(nombre);

-- ==========================================
-- 2. CATEGORÍAS DE PRODUCTOS
-- ==========================================
INSERT INTO categorias_productos (nombre, descripcion, activo) VALUES
('Electrónica', 'Productos electrónicos diversos', 1),
('Informática', 'Equipos y componentes informáticos', 1),
('Accesorios', 'Accesorios y complementos', 1),
('Software', 'Licencias y programas de software', 1),
('Servicios', 'Servicios profesionales', 1),
('Muebles', 'Muebles y equipamiento', 1)
ON DUPLICATE KEY UPDATE descripcion = VALUES(descripcion);

-- ==========================================
-- 3. MARCAS
-- ==========================================
INSERT INTO marcas (nombre, descripcion, activo) VALUES
('Apple', 'Marca Apple Inc.', 1),
('Dell', 'Marca Dell Technologies', 1),
('HP', 'Marca Hewlett-Packard', 1),
('Lenovo', 'Marca Lenovo Group', 1),
('Microsoft', 'Marca Microsoft Corporation', 1),
('Logitech', 'Marca Logitech International', 1),
('Samsung', 'Marca Samsung Electronics', 1),
('LG', 'Marca LG Electronics', 1),
('Genérica', 'Marca genérica/sin especificar', 1)
ON DUPLICATE KEY UPDATE descripcion = VALUES(descripcion);

-- ==========================================
-- 4. SUCURSALES
-- ==========================================
INSERT INTO sucursales (empresa_id, nombre, direccion, telefono, email, activo) VALUES
(1, 'Sucursal Matriz', 'San José, Centro', '+506 2000-0000', 'matriz@ursol.com', 1),
(1, 'Sucursal Alajuela', 'Alajuela, Centro', '+506 2111-1111', 'alajuela@ursol.com', 1),
(1, 'Sucursal Cartago', 'Cartago, Centro', '+506 2222-2222', 'cartago@ursol.com', 1)
ON DUPLICATE KEY UPDATE direccion = VALUES(direccion);

-- ==========================================
-- 5. ALMACENES
-- ==========================================
INSERT INTO almacenes (empresa_id, sucursal_id, nombre, codigo, descripcion, ubicacion, es_principal, activo) VALUES
(1, 1, 'Almacén Principal', 'ALM-001', 'Almacén central de la empresa', 'Bodega A, Piso 1', 1, 1),
(1, 1, 'Almacén Secundario', 'ALM-002', 'Almacén complementario', 'Bodega B, Piso 2', 0, 1),
(1, 2, 'Almacén Alajuela', 'ALM-003', 'Almacén sucursal Alajuela', 'Bodega Alajuela', 0, 1),
(1, 3, 'Almacén Cartago', 'ALM-004', 'Almacén sucursal Cartago', 'Bodega Cartago', 0, 1)
ON DUPLICATE KEY UPDATE descripcion = VALUES(descripcion);

-- ==========================================
-- 6. PROVEEDORES
-- ==========================================
INSERT INTO proveedores (empresa_id, tipo_identificacion, numero_identificacion, nombre, email, telefono, activo) VALUES
(1, '01', '3101111111', 'Mayorista Dell Costa Rica', 'ventas@dell-cr.com', '+506 2000-1111', 1),
(1, '01', '3102222222', 'HP Distribuidora CA', 'pedidos@hp-ca.com', '+506 2111-2222', 1),
(1, '01', '3103333333', 'Importadora Componentes TI', 'info@import-ti.com', '+506 2222-3333', 1),
(1, '01', '3104444444', 'Distribuidor Logitech', 'ventas@logi-cr.com', '+506 2333-4444', 1),
(1, '01', '3105555555', 'Proveedor Samsung', 'contacto@samsung-cr.com', '+506 2444-5555', 1)
ON DUPLICATE KEY UPDATE email = VALUES(email);

-- ==========================================
-- 7. PRODUCTOS (inserción directa)
-- ==========================================
INSERT INTO productos (empresa_id, categoria_id, codigo, nombre, descripcion, unidad_medida_id, marca_id, proveedor_id, precio_compra, precio_venta, stock_minimo, stock_maximo, tipo_producto, vende, compra) VALUES
(1, 1, 'PROD-001', 'Laptop Dell Inspiron 15', 'Laptop Dell Inspiron 15 pulgadas', 1, 2, 1, 800000, 950000, 2, 20, 'Producto', 1, 1),
(1, 1, 'PROD-010', 'Laptop HP 14 pulgadas', 'Laptop HP 14" con procesador Intel', 1, 3, 2, 650000, 800000, 2, 20, 'Producto', 1, 1),
(1, 3, 'PROD-002', 'Mouse Logitech Inalámbrico', 'Mouse inalámbrico 2.4GHz', 1, 6, 4, 15000, 20000, 10, 100, 'Producto', 1, 1),
(1, 3, 'PROD-003', 'Teclado Mecánico RGB', 'Teclado mecánico con iluminación RGB', 1, 6, 4, 35000, 50000, 5, 50, 'Producto', 1, 1),
(1, 2, 'PROD-004', 'Monitor LG 24 pulgadas FHD', 'Monitor LG Full HD 24"', 1, 8, 3, 120000, 160000, 3, 30, 'Producto', 1, 1),
(1, 3, 'PROD-005', 'Cable HDMI 3 metros', 'Cable HDMI versión 2.1 de 3 metros', 1, 9, 3, 8000, 12000, 20, 200, 'Producto', 1, 1),
(1, 2, 'PROD-050', 'Monitor Samsung 27"', 'Monitor Samsung 27" UHD 4K', 1, 7, 5, 250000, 350000, 2, 15, 'Producto', 1, 1),
(1, 2, 'PROD-051', 'iPad Apple 10"', 'iPad Apple 10 pulgadas última generación', 1, 1, 1, 450000, 600000, 1, 10, 'Producto', 1, 1),
(1, 4, 'PROD-052', 'Licencia Windows 11 Pro', 'Licencia de Software Microsoft Windows 11 Professional', 1, 5, 1, 180000, 240000, 5, 50, 'Servicio', 1, 1),
(1, 1, 'PROD-060', 'MacBook Pro 14"', 'Laptop MacBook Pro 14 pulgadas', 1, 1, 1, 1500000, 1900000, 1, 8, 'Producto', 1, 1)
ON DUPLICATE KEY UPDATE nombre = VALUES(nombre);

-- ==========================================
-- 8. CLIENTES
-- ==========================================
INSERT INTO clientes (empresa_id, tipo_identificacion, numero_identificacion, nombre, apellidos, email, telefono, limite_credito, plazo_credito_dias, activo) VALUES
(1, '01', '1234567890', 'José Manuel', 'Rodríguez García', 'jose.rodriguez@example.com', '+506 8765-4321', 500000, 30, 1),
(1, '01', '9876543210', 'María José', 'García López', 'maria.garcia@example.com', '+506 8876-5432', 750000, 45, 1),
(1, '01', '5555555555', 'Roberto', 'Pérez Vargas', 'roberto.perez@example.com', '+506 8945-6789', 600000, 30, 1),
(1, '01', '3101234567', 'Empresa ABC', 'Distribuidora', 'contacto@abcdist.com', '+506 2234-5678', 2000000, 60, 1),
(1, '01', '3102468135', 'Comercios Globales', 'Ltd', 'pedidos@comercios.com', '+506 2111-2222', 3000000, 60, 1)
ON DUPLICATE KEY UPDATE email = VALUES(email);

-- ==========================================
-- 9. EMPLEADOS
-- ==========================================
INSERT INTO empleados (empresa_id, nombre, primer_apellido, segundo_apellido, tipo_documento, numero_documento, email, telefono, activo) VALUES
(1, 'Carlos', 'González', 'Morales', 'cédula_residente', '7777777777', 'carlos.gonzalez@ursol.com', '+506 8234-5678', 1),
(1, 'María', 'López', 'Rodríguez', 'cédula_física', '8888888888', 'maria.lopez@ursol.com', '+506 8111-2222', 1),
(1, 'Juan', 'Sánchez', 'Vargas', 'cédula_física', '9999999999', 'juan.sanchez@ursol.com', '+506 8777-8888', 1),
(1, 'Pedro', 'Flores', 'García', 'cédula_física', '1010101010', 'pedro.flores@ursol.com', '+506 8999-0000', 1),
(1, 'Ana', 'Mendoza', 'López', 'cédula_física', '1111111111', 'ana.mendoza@ursol.com', '+506 8555-6666', 1)
ON DUPLICATE KEY UPDATE email = VALUES(email);

-- ==========================================
-- 10. INVENTARIO DE PRODUCTOS
-- ==========================================
INSERT INTO inventario_productos (almacen_id, producto_id, stock_actual, costo_promedio, stock_minimo, stock_maximo, activo) 
SELECT a.id, p.id, 10, p.precio_compra, 2, 20, 1
FROM almacenes a, productos p
WHERE a.es_principal = 1 AND p.id BETWEEN 1 AND 5
AND NOT EXISTS (SELECT 1 FROM inventario_productos WHERE almacen_id = a.id AND producto_id = p.id);

INSERT INTO inventario_productos (almacen_id, producto_id, stock_actual, costo_promedio, stock_minimo, stock_maximo, activo) 
SELECT a.id, p.id, 5, p.precio_compra, 1, 15, 1
FROM almacenes a, productos p
WHERE a.es_principal = 0 AND a.id = 2 AND p.id BETWEEN 1 AND 3
AND NOT EXISTS (SELECT 1 FROM inventario_productos WHERE almacen_id = a.id AND producto_id = p.id);

-- ==========================================
-- VERIFICACIÓN FINAL
-- ==========================================
SET FOREIGN_KEY_CHECKS=1;

-- Mostrar resumen de datos insertados
SELECT '=== RESUMEN DE DATOS ===' as Estado;

SELECT CONCAT('Unidades de Medida: ', COUNT(*)) FROM unidades_medida
UNION ALL
SELECT CONCAT('Categorías: ', COUNT(*)) FROM categorias_productos
UNION ALL
SELECT CONCAT('Marcas: ', COUNT(*)) FROM marcas
UNION ALL
SELECT CONCAT('Sucursales: ', COUNT(*)) FROM sucursales
UNION ALL
SELECT CONCAT('Almacenes: ', COUNT(*)) FROM almacenes
UNION ALL
SELECT CONCAT('Proveedores: ', COUNT(*)) FROM proveedores
UNION ALL
SELECT CONCAT('Productos: ', COUNT(*)) FROM productos
UNION ALL
SELECT CONCAT('Clientes: ', COUNT(*)) FROM clientes
UNION ALL
SELECT CONCAT('Empleados: ', COUNT(*)) FROM empleados
UNION ALL
SELECT CONCAT('Inventario: ', COUNT(*)) FROM inventario_productos;
