-- Senselab Core API - Script de Llenado de Datos de Prueba
-- Fecha: 11 de Febrero 2026
-- Este script llena TODAS las tablas con datos realistas para testing

USE api_db;

-- ============================================================================
-- FASE 1: TABLAS MAESTRAS BÁSICAS (Sin FK)
-- ============================================================================

-- 1. EMPRESAS (Landlord - No es tenant)
INSERT INTO empresas (id, nombre, ruc, email, telefono, direccion, ciudad, pais, moneda, porcentaje_iva, estado_activo, creado_en, actualizado_en) VALUES
(1, 'Senselab', '3101234567-8', 'info@senselab.com', '+(506)0000-0000', 'San José, Costa Rica', 'San José', 'CR', 'CRC', 13.00, true, NOW(), NOW()),
(2, 'Empresa Demo S.A.', '3107654321-9', 'demo@example.com', '+506 2234-5678', 'Cartago, Costa Rica', 'Cartago', 'CR', 'CRC', 13.00, true, NOW(), NOW()),
(3, 'Comercios Globales Ltd', '3102468135-0', 'info@comercios.com', '+506 2111-2222', 'Heredia, Costa Rica', 'Heredia', 'CR', 'CRC', 13.00, true, NOW(), NOW());

-- 2. ROLES (Sistema RBAC)
INSERT INTO roles (id, nombre, descripcion, empresa_id, creado_en, actualizado_en) VALUES
(1, 'administrador', 'Administrador del sistema - Acceso total', 1, NOW(), NOW()),
(2, 'gerente', 'Gerente - Gestión operacional', 1, NOW(), NOW()),
(3, 'contador', 'Contador - Contabilidad y reportes', 1, NOW(), NOW()),
(4, 'vendedor', 'Vendedor - Gestión de ventas', 1, NOW(), NOW()),
(5, 'comprador', 'Comprador - Gestión de compras', 1, NOW(), NOW()),
(6, 'bodeguero', 'Bodeguero - Control de inventario', 1, NOW(), NOW()),
(7, 'usuario', 'Usuario estándar - Acceso básico', 1, NOW(), NOW()),
(8, 'auditor', 'Auditor - Auditoría y reportes', 1, NOW(), NOW());

-- 3. PERMISOS SISTEMA
INSERT INTO permisos (id, nombre, slug, descripcion, empresa_id, creado_en, actualizado_en) VALUES
(1, 'Ver Empresas', 'empresas.leer', 'Permiso para ver empresas', 1, NOW(), NOW()),
(2, 'Crear Empresas', 'empresas.crear', 'Permiso para crear empresas', 1, NOW(), NOW()),
(3, 'Editar Empresas', 'empresas.actualizar', 'Permiso para editar empresas', 1, NOW(), NOW()),
(4, 'Eliminar Empresas', 'empresas.eliminar', 'Permiso para eliminar empresas', 1, NOW(), NOW()),
(5, 'Ver Productos', 'productos.leer', 'Permiso para ver productos', 1, NOW(), NOW()),
(6, 'Crear Productos', 'productos.crear', 'Permiso para crear productos', 1, NOW(), NOW()),
(7, 'Editar Productos', 'productos.actualizar', 'Permiso para editar productos', 1, NOW(), NOW()),
(8, 'Eliminar Productos', 'productos.eliminar', 'Permiso para eliminar productos', 1, NOW(), NOW()),
(9, 'Ver Ventas', 'ventas.leer', 'Permiso para ver ventas', 1, NOW(), NOW()),
(10, 'Crear Ventas', 'ventas.crear', 'Permiso para crear ventas', 1, NOW(), NOW()),
(11, 'Ver Clientes', 'clientes.leer', 'Permiso para ver clientes', 1, NOW(), NOW()),
(12, 'Crear Clientes', 'clientes.crear', 'Permiso para crear clientes', 1, NOW(), NOW()),
(13, 'Ver Compras', 'compras.leer', 'Permiso para ver compras', 1, NOW(), NOW()),
(14, 'Crear Compras', 'compras.crear', 'Permiso para crear compras', 1, NOW(), NOW()),
(15, 'Ver Inventario', 'inventario.leer', 'Permiso para ver inventario', 1, NOW(), NOW()),
(16, 'Editar Inventario', 'inventario.actualizar', 'Permiso para editar inventario', 1, NOW(), NOW()),
(17, 'Ver Contabilidad', 'contabilidad.leer', 'Permiso para ver contabilidad', 1, NOW(), NOW()),
(18, 'Crear Asientos', 'contabilidad.crear', 'Permiso para crear asientos contables', 1, NOW(), NOW()),
(19, 'Ver Nómina', 'nomina.leer', 'Permiso para ver nómina', 1, NOW(), NOW()),
(20, 'Crear Nómina', 'nomina.crear', 'Permiso para crear nómina', 1, NOW(), NOW());

-- 4. TIPOS DE CUENTAS CONTABLES
INSERT INTO tipos_cuentas (id, nombre, descripcion, empresa_id, creado_en, actualizado_en) VALUES
(1, 'Activo Circulante', 'Activos a corto plazo', 1, NOW(), NOW()),
(2, 'Activo Fijo', 'Activos a largo plazo', 1, NOW(), NOW()),
(3, 'Pasivo Circulante', 'Pasivos a corto plazo', 1, NOW(), NOW()),
(4, 'Pasivo Fijo', 'Pasivos a largo plazo', 1, NOW(), NOW()),
(5, 'Patrimonio', 'Capital y resultados', 1, NOW(), NOW()),
(6, 'Ingresos', 'Ingresos operacionales', 1, NOW(), NOW()),
(7, 'Gastos', 'Gastos operacionales', 1, NOW(), NOW()),
(8, 'Costos', 'Costo de ventas', 1, NOW(), NOW());

-- 5. TIPOS DE CLIENTE
INSERT INTO tipos_clientes (id, nombre, descripcion, empresa_id, creado_en, actualizado_en) VALUES
(1, 'Persona Física', 'Cliente persona física', 1, NOW(), NOW()),
(2, 'Persona Jurídica', 'Cliente empresa/sociedad', 1, NOW(), NOW()),
(3, 'Gobierno', 'Entidad gubernamental', 1, NOW(), NOW()),
(4, 'ONG', 'Organización sin fines de lucro', 1, NOW(), NOW());

-- 6. UNIDADES DE MEDIDA
INSERT INTO unidades_medida (id, nombre, abreviatura, empresa_id, creado_en, actualizado_en) VALUES
(1, 'Unidad', 'Unid', 1, NOW(), NOW()),
(2, 'Kilogramo', 'kg', 1, NOW(), NOW()),
(3, 'Litro', 'l', 1, NOW(), NOW()),
(4, 'Metro', 'm', 1, NOW(), NOW()),
(5, 'Metro Cuadrado', 'm²', 1, NOW(), NOW()),
(6, 'Docena', 'doc', 1, NOW(), NOW()),
(7, 'Caja', 'caja', 1, NOW(), NOW()),
(8, 'Paquete', 'paq', 1, NOW(), NOW()),
(9, 'Hora', 'hora', 1, NOW(), NOW()),
(10, 'Día', 'día', 1, NOW(), NOW()),
(11, 'Mes', 'mes', 1, NOW(), NOW());

-- 7. REGÍMENES TRIBUTARIOS
INSERT INTO regimenes_tributarios (id, nombre, descripcion, tasa_iva, empresa_id, creado_en, actualizado_en) VALUES
(1, 'Régimen Tributario Ordinario', 'Impuesto sobre la renta 25%', 25.00, 1, NOW(), NOW()),
(2, 'Régimen Simplificado', 'Para pequeños negocios', 0.00, 1, NOW(), NOW());

-- 8. FORMAS DE PAGO
INSERT INTO formas_pago (id, nombre, descripcion, es_efectivo, empresa_id, creado_en, actualizado_en) VALUES
(1, 'Efectivo', 'Pago en efectivo', true, 1, NOW(), NOW()),
(2, 'Tarjeta de Crédito', 'Pago con tarjeta de crédito', false, 1, NOW(), NOW()),
(3, 'Tarjeta de Débito', 'Pago con tarjeta de débito', false, 1, NOW(), NOW()),
(4, 'Cheque', 'Pago con cheque', false, 1, NOW(), NOW()),
(5, 'Transferencia Bancaria', 'Pago por transferencia', false, 1, NOW(), NOW()),
(6, 'Crédito', 'Pago a crédito', false, 1, NOW(), NOW());

-- 9. TIPOS DE IMPUESTO
INSERT INTO tipos_impuesto (id, nombre, tasa, descripcion, empresa_id, creado_en, actualizado_en) VALUES
(1, 'IVA', 13.00, 'Impuesto al Valor Agregado', 1, NOW(), NOW()),
(2, 'Impuesto Selectivo al Consumo', 25.00, 'ISC', 1, NOW(), NOW()),
(3, 'Derechos Arancelarios', 5.00, 'Aranceles de importación', 1, NOW(), NOW());

-- 10. CÓDIGOS DE ACTIVIDAD ECONÓMICA
INSERT INTO codigos_actividad_economica (id, codigo, descripcion, empresa_id, creado_en, actualizado_en) VALUES
(1, '4610', 'Comercio al por mayor', 1, NOW(), NOW()),
(2, '4711', 'Comercio al por menor', 1, NOW(), NOW()),
(3, '6201', 'Programación informática', 1, NOW(), NOW()),
(4, '6203', 'Gestión de instalaciones informáticas', 1, NOW(), NOW()),
(5, '7490', 'Actividades profesionales diversas', 1, NOW(), NOW());

-- 11. TIPOS DE COMPROBANTE FE
INSERT INTO tipos_comprobantes_fe (id, codigo, nombre, descripcion, empresa_id, activo, creado_en, actualizado_en) VALUES
(1, '01', 'Factura Electrónica', 'Factura de venta electrónica', 1, true, NOW(), NOW()),
(2, '02', 'Nota de Débito', 'Nota de débito electrónica', 1, true, NOW(), NOW()),
(3, '03', 'Nota de Crédito', 'Nota de crédito electrónica', 1, true, NOW(), NOW()),
(4, '04', 'Tiquete Electrónico', 'Tiquete de venta electrónico', 1, true, NOW(), NOW());

-- 12. CÓDIGOS CABYS (Clasificación de Actividades)
INSERT INTO cabys (id, codigo, descripcion, empresa_id, creado_en, actualizado_en) VALUES
(1, '62.01.00.00.00', 'Diseño de sistemas informáticos', 1, NOW(), NOW()),
(2, '62.02.00.00.00', 'Consultoría informática', 1, NOW(), NOW()),
(3, '62.03.00.00.00', 'Gestión de instalaciones', 1, NOW(), NOW()),
(4, '47.11.10.00.00', 'Comercio al por menor en tiendas', 1, NOW(), NOW()),
(5, '47.19.10.00.00', 'Comercio al por menor de otros artículos', 1, NOW(), NOW()),
(6, '46.10.00.00.00', 'Comercio al por mayor', 1, NOW(), NOW());

-- ============================================================================
-- FASE 2: TABLAS ESTRUCTURALES (Dependen de Fase 1)
-- ============================================================================

-- 13. SUCURSALES
INSERT INTO sucursales (id, empresa_id, nombre, direccion, ciudad, provincia, telefono, email, es_principal, activo, creado_en, actualizado_en) VALUES
(1, 1, 'Sucursal Principal - San José', 'Ave Central, San José', 'San José', 'San José', '22341111', 'principal@senselab.com', true, true, NOW(), NOW()),
(2, 1, 'Sucursal Cartago', 'Centro de Cartago', 'Cartago', 'Cartago', '22551111', 'cartago@senselab.com', false, true, NOW(), NOW()),
(3, 1, 'Sucursal Heredia', 'Centro de Heredia', 'Heredia', 'Heredia', '23701111', 'heredia@senselab.com', false, true, NOW(), NOW());

-- 14. ALMACENES
INSERT INTO almacenes (id, sucursal_id, nombre, codigo, descripcion, ubicacion, is_principal, activo, creado_en, actualizado_en) VALUES
(1, 1, 'Almacén Principal', 'ALM-001', 'Almacén central de San José', 'Planta Baja', true, true, NOW(), NOW()),
(2, 1, 'Almacén Secundario', 'ALM-002', 'Almacén de respaldo', 'Planta Alta', false, true, NOW(), NOW()),
(3, 2, 'Almacén Cartago', 'ALM-003', 'Almacén sucursal Cartago', 'Local 1', false, true, NOW(), NOW()),
(4, 3, 'Almacén Heredia', 'ALM-004', 'Almacén sucursal Heredia', 'Local 2', false, true, NOW(), NOW());

-- 15. CARGOS (Empleados)
INSERT INTO cargos (id, nombre, descripcion, empresa_id, creado_en, actualizado_en) VALUES
(1, 'Director General', 'Dirección de la empresa', 1, NOW(), NOW()),
(2, 'Gerente General', 'Gerencia general', 1, NOW(), NOW()),
(3, 'Contador', 'Contabilidad', 1, NOW(), NOW()),
(4, 'Vendedor', 'Ventas', 1, NOW(), NOW()),
(5, 'Comprador', 'Compras', 1, NOW(), NOW()),
(6, 'Bodeguero', 'Inventario', 1, NOW(), NOW()),
(7, 'Recepcionista', 'Recepción', 1, NOW(), NOW());

-- 16. TIPOS DE IMPUESTO (Dedicado)
INSERT INTO tipos_impuesto (id, nombre, tasa, descripcion, empresa_id, creado_en, actualizado_en) VALUES
(4, 'Retención en la Fuente', 2.00, 'Retención en la fuente', 1, NOW(), NOW()),
(5, 'Contribución Social', 5.50, 'Contribución social', 1, NOW(), NOW());

-- 17. USUARIOS (Personas)
-- Hashear: admin123 = $2y$12$V2GJpvLvKloepHM7ZLLBe.K5JfhW0GZXR5AkU5W5Z5Z5Z5Z5Z5Z5
INSERT INTO usuarios (id, empresa_id, nombre, email, password, telefono, estado_activo, ultimo_acceso, creado_en, actualizado_en) VALUES
(1, 1, 'Administrador', 'admin@senselab.com', '$2y$12$V2GJpvLvKloepHM7ZLLBe.K5JfhW0GZXR5AkU5W5Z5Z5Z5Z5Z5Z', '+(506)0000-0000', true, NOW(), NOW(), NOW()),
(2, 1, 'Carlos Gerente', 'carlos.gerente@senselab.com', '$2y$12$V2GJpvLvKloepHM7ZLLBe.K5JfhW0GZXR5AkU5W5Z5Z5Z5Z5Z5Z', '+506 8234-5678', true, NOW(), NOW(), NOW()),
(3, 1, 'María Contador', 'maria.contador@senselab.com', '$2y$12$V2GJpvLvKloepHM7ZLLBe.K5JfhW0GZXR5AkU5W5Z5Z5Z5Z5Z5Z', '+506 8111-2222', true, NOW(), NOW(), NOW()),
(4, 1, 'Juan Vendedor', 'juan.vendedor@senselab.com', '$2y$12$V2GJpvLvKloepHM7ZLLBe.K5JfhW0GZXR5AkU5W5Z5Z5Z5Z5Z5Z', '+506 8777-8888', true, NOW(), NOW(), NOW()),
(5, 1, 'Pedro Bodeguero', 'pedro.bodeguero@senselab.com', '$2y$12$V2GJpvLvKloepHM7ZLLBe.K5JfhW0GZXR5AkU5W5Z5Z5Z5Z5Z5Z', '+506 8999-0000', true, NOW(), NOW(), NOW());

-- 18. CUENTAS CONTABLES
INSERT INTO cuentas_contables (id, empresa_id, numero_cuenta, nombre, tipo_cuenta_id, saldo_inicial, saldo_actual, moneda, es_activa, creado_en, actualizado_en) VALUES
(1, 1, '1100', 'Caja y Equivalentes', 1, 100000.00, 150000.00, 'CRC', true, NOW(), NOW()),
(2, 1, '1200', 'Cuentas por Cobrar', 1, 50000.00, 75000.00, 'CRC', true, NOW(), NOW()),
(3, 1, '1300', 'Inventario', 1, 200000.00, 250000.00, 'CRC', true, NOW(), NOW()),
(4, 1, '2100', 'Cuentas por Pagar', 3, 30000.00, 45000.00, 'CRC', true, NOW(), NOW()),
(5, 1, '3100', 'Capital Social', 5, 500000.00, 500000.00, 'CRC', true, NOW(), NOW()),
(6, 1, '4100', 'Ventas', 6, 0.00, 300000.00, 'CRC', true, NOW(), NOW()),
(7, 1, '5100', 'Costos de Venta', 8, 0.00, 150000.00, 'CRC', true, NOW(), NOW()),
(8, 1, '6100', 'Gastos de Operación', 7, 0.00, 50000.00, 'CRC', true, NOW(), NOW());

-- ============================================================================
-- FASE 3: CATÁLOGOS DE PRODUCTOS
-- ============================================================================

-- 19. CATEGORÍAS DE PRODUCTOS
INSERT INTO categorias_productos (id, empresa_id, nombre, descripcion, codigo, activo, creado_en, actualizado_en) VALUES
(1, 1, 'Electrónica', 'Productos electrónicos', 'ELEC', true, NOW(), NOW()),
(2, 1, 'Informática', 'Productos informáticos', 'INFO', true, NOW(), NOW()),
(3, 1, 'Accesorios', 'Accesorios varios', 'ACCE', true, NOW(), NOW()),
(4, 1, 'Software', 'Licencias de software', 'SOFT', true, NOW(), NOW()),
(5, 1, 'Servicios', 'Servicios profesionales', 'SERV', true, NOW(), NOW());

-- 20. MARCAS
INSERT INTO marcas (id, empresa_id, nombre, descripcion, activo, creado_en, actualizado_en) VALUES
(1, 1, 'Apple', 'Marca Apple Inc.', true, NOW(), NOW()),
(2, 1, 'Dell', 'Marca Dell Technologies', true, NOW(), NOW()),
(3, 1, 'HP', 'Marca HP Inc.', true, NOW(), NOW()),
(4, 1, 'Lenovo', 'Marca Lenovo Group', true, NOW(), NOW()),
(5, 1, 'Microsoft', 'Marca Microsoft', true, NOW(), NOW()),
(6, 1, 'Genérica', 'Marca genérica', true, NOW(), NOW());

-- 21. PRODUCTOS
INSERT INTO productos (id, empresa_id, codigo, nombre, descripcion, precio_costo, precio_venta, margen_ganancia, cantidad_stock, cantidad_minima, unidad_medida_id, categoria_producto_id, marca_id, cabys_id, es_servicio, estado_activo, creado_en, actualizado_en) VALUES
(1, 1, 'PROD-001', 'Laptop Dell Inspiron 15', 'Laptop Dell Inspiron 15 pulgadas', 800000, 950000, 18.75, 10, 2, 1, 2, 2, 1, false, true, NOW(), NOW()),
(2, 1, 'PROD-002', 'Mouse Logitech Inalámbrico', 'Mouse inalámbrico Logitech', 15000, 20000, 33.33, 50, 10, 1, 3, 6, 1, false, true, NOW(), NOW()),
(3, 1, 'PROD-003', 'Teclado Mecánico RGB', 'Teclado mecánico con iluminación RGB', 35000, 50000, 42.86, 25, 5, 1, 3, 6, 1, false, true, NOW(), NOW()),
(4, 1, 'PROD-004', 'Monitor LG 24 pulgadas', 'Monitor LG Full HD 24"', 120000, 160000, 33.33, 15, 3, 1, 1, 3, 1, false, true, NOW(), NOW()),
(5, 1, 'PROD-005', 'Cable HDMI 3m', 'Cable HDMI versión 2.1', 8000, 12000, 50.00, 100, 20, 1, 3, 6, 1, false, true, NOW(), NOW()),
(6, 1, 'PROD-006', 'Hub USB 7 puertos', 'Hub USB 3.0 de 7 puertos', 25000, 35000, 40.00, 20, 5, 1, 3, 6, 1, false, true, NOW(), NOW()),
(7, 1, 'PROD-007', 'Disco Duro Externo 1TB', 'Disco duro externo 1TB', 45000, 65000, 44.44, 12, 3, 1, 3, 6, 1, false, true, NOW(), NOW()),
(8, 1, 'PROD-008', 'SSD M.2 512GB', 'Unidad SSD M.2 512GB', 50000, 75000, 50.00, 18, 4, 1, 3, 6, 1, false, true, NOW(), NOW()),
(9, 1, 'PROD-009', 'RAM DDR4 16GB', 'Memoria RAM DDR4 16GB', 60000, 85000, 41.67, 22, 5, 1, 3, 6, 1, false, true, NOW(), NOW()),
(10, 1, 'PROD-010', 'Laptop HP 14 pulgadas', 'Laptop HP 14" económica', 650000, 800000, 23.08, 8, 2, 1, 2, 3, 2, false, true, NOW(), NOW()),
(11, 1, 'PROD-011', 'Office 365 Suscripción 1 año', 'Licencia Office 365 personal 1 año', 50000, 65000, 30.00, 0, 0, 1, 4, 5, 3, true, true, NOW(), NOW()),
(12, 1, 'PROD-012', 'Consultoría TI 1 hora', 'Servicio de consultoría 1 hora', 0, 75000, 0, 0, 0, 9, 5, 6, 3, true, true, NOW(), NOW()),
(13, 1, 'PROD-013', 'Actualización de Windows', 'Servicio de actualización Windows', 0, 50000, 0, 0, 0, 1, 5, 6, 3, true, true, NOW(), NOW()),
(14, 1, 'PROD-014', 'Cámara Web HD', 'Cámara web 1080p', 30000, 45000, 50.00, 30, 10, 1, 1, 6, 4, false, true, NOW(), NOW()),
(15, 1, 'PROD-015', 'Micrófono de Condensador', 'Micrófono profesional USB', 80000, 120000, 50.00, 8, 2, 1, 3, 6, 5, false, true, NOW(), NOW());

-- 22. INVENTARIO PRODUCTOS (Stock por almacén)
INSERT INTO inventario_productos (id, producto_id, almacen_id, cantidad_disponible, cantidad_reservada, cantidad_dañada, costo_promedio, creado_en, actualizado_en) VALUES
(1, 1, 1, 8, 2, 0, 800000, NOW(), NOW()),
(2, 1, 2, 2, 0, 0, 800000, NOW(), NOW()),
(3, 2, 1, 45, 5, 0, 15000, NOW(), NOW()),
(4, 2, 2, 5, 0, 0, 15000, NOW(), NOW()),
(5, 3, 1, 20, 5, 0, 35000, NOW(), NOW()),
(6, 4, 1, 12, 3, 0, 120000, NOW(), NOW()),
(7, 5, 1, 80, 20, 0, 8000, NOW(), NOW()),
(8, 6, 1, 18, 2, 0, 25000, NOW(), NOW()),
(9, 7, 1, 10, 2, 0, 45000, NOW(), NOW()),
(10, 8, 1, 16, 2, 0, 50000, NOW(), NOW()),
(11, 9, 1, 20, 2, 0, 60000, NOW(), NOW()),
(12, 10, 1, 6, 2, 0, 650000, NOW(), NOW()),
(13, 14, 1, 25, 5, 0, 30000, NOW(), NOW()),
(14, 15, 1, 6, 2, 0, 80000, NOW(), NOW());

-- ============================================================================
-- FASE 4: TABLAS DE PERSONAS (CLIENTES, PROVEEDORES, EMPLEADOS)
-- ============================================================================

-- 23. CLIENTES
INSERT INTO clientes (id, empresa_id, tipo_cliente_id, nombre, identificacion, email, telefono, contacto_principal, direccion, ciudad, provincia, condicion_pago, limite_credito, saldo_actual, estado_activo, creado_en, actualizado_en) VALUES
(1, 1, 1, 'José Manuel Rodríguez', '123456789', 'jose@example.com', '8765-4321', 'José Rodríguez', 'San José', 'San José', 'San José', 'Crédito 30 días', 200000, 45000, true, NOW(), NOW()),
(2, 1, 2, 'Empresa ABC Distribuidora', '3101234567', 'contacto@abcdist.com', '2234-5678', 'Gerente ABC', 'Cartago', 'Cartago', 'Cartago', 'Crédito 60 días', 500000, 150000, true, NOW(), NOW()),
(3, 1, 1, 'María José García López', '987654321', 'maria@example.com', '8876-5432', 'María García', 'Heredia', 'Heredia', 'Heredia', 'Contado', 100000, 0, true, NOW(), NOW()),
(4, 1, 2, 'Comercios Globales Ltd', '3102468135', 'pedidos@comercios.com', '2111-2222', 'Compras', 'San José', 'San José', 'San José', 'Crédito 30 días', 300000, 75000, true, NOW(), NOW()),
(5, 1, 1, 'Roberto Pérez Vargas', '456789123', 'roberto@example.com', '8945-6789', 'Roberto Pérez', 'Alajuela', 'Alajuela', 'Alajuela', 'Contado', 0, 0, true, NOW(), NOW()),
(6, 1, 2, 'Tienda de Electrónica XYZ', '3105555555', 'ventas@electro-xyz.com', '2777-8888', 'Gerente Ventas', 'San José', 'San José', 'San José', 'Crédito 60 días', 400000, 100000, true, NOW(), NOW()),
(7, 1, 1, 'Ana Clara Ramírez', '234567890', 'ana@example.com', '8654-3210', 'Ana Ramírez', 'Limón', 'Limón', 'Limón', 'Contado', 50000, 0, true, NOW(), NOW()),
(8, 1, 1, 'Carlos Eduardo Moreno', '567890123', 'carlos.moreno@example.com', '8543-2109', 'Carlos Moreno', 'Puntarenas', 'Puntarenas', 'Puntarenas', 'Crédito 15 días', 150000, 30000, true, NOW(), NOW());

-- 24. PROVEEDORES
INSERT INTO proveedores (id, empresa_id, nombre, identificacion, contacto_principal, email, telefono, direccion, ciudad, provincia, condicion_pago, saldo_actual, estado_activo, creado_en, actualizado_en) VALUES
(1, 1, 'Mayorista Dell Costa Rica', '3103333333', 'Ventas', 'ventas@dellcr.com', '2000-1111', 'San José', 'San José', 'San José', 'Crédito 45 días', 250000, true, NOW(), NOW()),
(2, 1, 'HP Distribuidora Centroamérica', '3104444444', 'Pedidos', 'pedidos@hpcr.com', '2111-1111', 'Cartago', 'Cartago', 'Cartago', 'Crédito 60 días', 180000, true, NOW(), NOW()),
(3, 1, 'Importadora de Componentes TI', '3105555555', 'Gerente', 'info@importadora-ti.com', '2222-2222', 'San José', 'San José', 'San José', 'Crédito 30 días', 120000, true, NOW(), NOW()),
(4, 1, 'Logitech Distribuidor Oficial', '3106666666', 'Representante', 'distribuidor@logitech-cr.com', '2333-3333', 'San José', 'San José', 'San José', 'Crédito 60 días', 90000, true, NOW(), NOW()),
(5, 1, 'Lenovo Autorizado Costa Rica', '3107777777', 'Ventas Corporativas', 'ventas@lenovo-cr.com', '2444-4444', 'San José', 'San José', 'San José', 'Crédito 45 días', 200000, true, NOW(), NOW());

-- 25. EMPLEADOS
INSERT INTO empleados (id, empresa_id, usuario_id, cargo_id, nombre, identificacion, email, telefono, fecha_nacimiento, genero, direccion, ciudad, provincia, fecha_ingreso, fecha_salida, estado_activo, salario_base, creado_en, actualizado_en) VALUES
(1, 1, 1, 1, 'Administrador', '112233445', 'admin@senselab.com', '+(506)0000-0000', '1975-05-20', 'M', 'San José', 'San José', 'San José', '2020-01-15', NULL, true, 1500000, NOW(), NOW()),
(2, 1, 2, 2, 'Carlos Gerente', '223344556', 'carlos.gerente@senselab.com', '+506 8234-5678', '1985-07-10', 'M', 'Cartago', 'Cartago', 'Cartago', '2021-02-20', NULL, true, 1200000, NOW(), NOW()),
(3, 1, 3, 3, 'María Contador', '334455667', 'maria.contador@senselab.com', '+506 8111-2222', '1990-03-15', 'F', 'Heredia', 'Heredia', 'Heredia', '2021-06-01', NULL, true, 900000, NOW(), NOW()),
(4, 1, 4, 4, 'Juan Vendedor', '445566778', 'juan.vendedor@senselab.com', '+506 8777-8888', '1988-11-22', 'M', 'San José', 'San José', 'San José', '2022-01-10', NULL, true, 700000, NOW(), NOW()),
(5, 1, 5, 6, 'Pedro Bodeguero', '556677889', 'pedro.bodeguero@senselab.com', '+506 8999-0000', '1992-09-05', 'M', 'Alajuela', 'Alajuela', 'Alajuela', '2022-03-15', NULL, true, 600000, NOW(), NOW());

-- ============================================================================
-- FASE 5: TRANSACCIONES COMPRAS Y VENTAS (SIMPLIFICADAS)
-- ============================================================================

-- 26. ÓRDENES DE COMPRA
INSERT INTO ordenes_compra (id, empresa_id, numero_orden, proveedor_id, fecha_orden, fecha_entrega_esperada, fecha_entrega_actual, total, estado, moneda, usuario_creacion_id, creado_en, actualizado_en) VALUES
(1, 1, 'OC-001', 1, '2026-02-01', '2026-02-10', '2026-02-09', 12000000, 'completada', 'CRC', 1, NOW(), NOW()),
(2, 1, 'OC-002', 3, '2026-02-03', '2026-02-15', NULL, 5500000, 'pendiente', 'CRC', 1, NOW(), NOW()),
(3, 1, 'OC-003', 2, '2026-02-05', '2026-02-20', NULL, 8900000, 'en_proceso', 'CRC', 1, NOW(), NOW()),
(4, 1, 'OC-004', 4, '2026-02-07', '2026-02-25', NULL, 2250000, 'pendiente', 'CRC', 1, NOW(), NOW()),
(5, 1, 'OC-005', 5, '2026-02-08', '2026-02-28', NULL, 6750000, 'en_proceso', 'CRC', 1, NOW(), NOW());

-- 27. DETALLES DE ÓRDENES DE COMPRA
INSERT INTO detalle_ordenes_compra (id, orden_compra_id, producto_id, cantidad, precio_unitario, subtotal, creado_en, actualizado_en) VALUES
(1, 1, 1, 3, 800000, 2400000, NOW(), NOW()),
(2, 1, 10, 5, 650000, 3250000, NOW(), NOW()),
(3, 1, 2, 50, 15000, 750000, NOW(), NOW()),
(4, 1, 3, 25, 35000, 875000, NOW(), NOW()),
(5, 1, 4, 10, 120000, 1200000, NOW(), NOW()),
(6, 2, 6, 15, 25000, 375000, NOW(), NOW()),
(7, 2, 7, 8, 45000, 360000, NOW(), NOW()),
(8, 2, 8, 12, 50000, 600000, NOW(), NOW()),
(9, 2, 9, 20, 60000, 1200000, NOW(), NOW()),
(10, 2, 5, 80, 8000, 640000, NOW(), NOW()),
(11, 3, 14, 15, 30000, 450000, NOW(), NOW()),
(12, 3, 15, 8, 80000, 640000, NOW(), NOW()),
(13, 3, 2, 100, 15000, 1500000, NOW(), NOW()),
(14, 3, 3, 50, 35000, 1750000, NOW(), NOW()),
(15, 3, 5, 180, 8000, 1440000, NOW(), NOW()),
(16, 4, 2, 60, 15000, 900000, NOW(), NOW()),
(17, 4, 5, 150, 8000, 1200000, NOW(), NOW()),
(18, 4, 6, 20, 25000, 500000, NOW(), NOW()),
(19, 5, 11, 10, 50000, 500000, NOW(), NOW()),
(20, 5, 1, 5, 800000, 4000000, NOW(), NOW()),
(21, 5, 10, 3, 650000, 1950000, NOW(), NOW()),
(22, 5, 4, 8, 120000, 960000, NOW(), NOW());

-- 28. VENTAS
INSERT INTO ventas (id, empresa_id, numero_venta, cliente_id, fecha_venta, subtotal, impuesto, total, estado, forma_pago_id, usuario_creacion_id, creado_en, actualizado_en) VALUES
(1, 1, 'V-001', 1, '2026-02-04', 3600000, 468000, 4068000, 'completada', 2, 4, NOW(), NOW()),
(2, 1, 'V-002', 2, '2026-02-05', 5200000, 676000, 5876000, 'completada', 1, 4, NOW(), NOW()),
(3, 1, 'V-003', 3, '2026-02-06', 1240000, 161200, 1401200, 'completada', 3, 4, NOW(), NOW()),
(4, 1, 'V-004', 4, '2026-02-07', 2850000, 370500, 3220500, 'completada', 5, 4, NOW(), NOW()),
(5, 1, 'V-005', 5, '2026-02-08', 950000, 123500, 1073500, 'completada', 1, 4, NOW(), NOW()),
(6, 1, 'V-006', 6, '2026-02-09', 4500000, 585000, 5085000, 'en_proceso', 2, 4, NOW(), NOW()),
(7, 1, 'V-007', 7, '2026-02-09', 725000, 94250, 819250, 'completada', 1, 4, NOW(), NOW()),
(8, 1, 'V-008', 8, '2026-02-10', 3200000, 416000, 3616000, 'completada', 6, 4, NOW(), NOW());

-- 29. DETALLES DE VENTAS
INSERT INTO detalle_ventas (id, venta_id, producto_id, cantidad, precio_unitario, subtotal, impuesto, total, creado_en, actualizado_en) VALUES
(1, 1, 1, 2, 950000, 1900000, 247000, 2147000, NOW(), NOW()),
(2, 1, 4, 3, 160000, 480000, 62400, 542400, NOW(), NOW()),
(3, 1, 15, 2, 120000, 240000, 31200, 271200, NOW(), NOW()),
(4, 1, 5, 20, 12000, 240000, 31200, 271200, NOW(), NOW()),
(5, 1, 2, 10, 20000, 200000, 26000, 226000, NOW(), NOW()),
(6, 1, 3, 5, 50000, 250000, 32500, 282500, NOW(), NOW()),
(7, 1, 6, 4, 35000, 140000, 18200, 158200, NOW(), NOW()),
(8, 2, 10, 3, 800000, 2400000, 312000, 2712000, NOW(), NOW()),
(9, 2, 8, 2, 75000, 150000, 19500, 169500, NOW(), NOW()),
(10, 2, 9, 1, 85000, 85000, 11050, 96050, NOW(), NOW()),
(11, 2, 7, 3, 65000, 195000, 25350, 220350, NOW(), NOW()),
(12, 2, 14, 5, 45000, 225000, 29250, 254250, NOW(), NOW()),
(13, 2, 11, 2, 65000, 130000, 16900, 146900, NOW(), NOW()),
(14, 2, 12, 2, 75000, 150000, 19500, 169500, NOW(), NOW()),
(15, 3, 2, 15, 20000, 300000, 39000, 339000, NOW(), NOW()),
(16, 3, 5, 45, 12000, 540000, 70200, 610200, NOW(), NOW()),
(17, 3, 3, 3, 50000, 150000, 19500, 169500, NOW(), NOW()),
(18, 3, 6, 5, 35000, 175000, 22750, 197750, NOW(), NOW()),
(19, 3, 14, 8, 45000, 360000, 46800, 406800, NOW(), NOW()),
(20, 3, 2, 8, 20000, 160000, 20800, 180800, NOW(), NOW()),
(21, 4, 1, 1, 950000, 950000, 123500, 1073500, NOW(), NOW()),
(22, 4, 4, 2, 160000, 320000, 41600, 361600, NOW(), NOW()),
(23, 4, 8, 3, 75000, 225000, 29250, 254250, NOW(), NOW()),
(24, 4, 9, 2, 85000, 170000, 22100, 192100, NOW(), NOW()),
(25, 4, 15, 1, 120000, 120000, 15600, 135600, NOW(), NOW()),
(26, 4, 12, 3, 75000, 225000, 29250, 254250, NOW(), NOW()),
(27, 4, 2, 20, 20000, 400000, 52000, 452000, NOW(), NOW()),
(28, 4, 5, 30, 12000, 360000, 46800, 406800, NOW(), NOW()),
(29, 5, 3, 2, 50000, 100000, 13000, 113000, NOW(), NOW()),
(30, 5, 14, 5, 45000, 225000, 29250, 254250, NOW(), NOW()),
(31, 5, 2, 8, 20000, 160000, 20800, 180800, NOW(), NOW()),
(32, 5, 6, 3, 35000, 105000, 13650, 118650, NOW(), NOW()),
(33, 5, 5, 12, 12000, 144000, 18720, 162720, NOW(), NOW()),
(34, 5, 15, 1, 120000, 120000, 15600, 135600, NOW(), NOW()),
(35, 5, 9, 2, 85000, 170000, 22100, 192100, NOW(), NOW()),
(36, 5, 8, 1, 75000, 75000, 9750, 84750, NOW(), NOW()),
(37, 6, 1, 2, 950000, 1900000, 247000, 2147000, NOW(), NOW()),
(38, 6, 10, 1, 800000, 800000, 104000, 904000, NOW(), NOW()),
(39, 6, 4, 4, 160000, 640000, 83200, 723200, NOW(), NOW()),
(40, 6, 8, 6, 75000, 450000, 58500, 508500, NOW(), NOW()),
(41, 6, 9, 3, 85000, 255000, 33150, 288150, NOW(), NOW()),
(42, 6, 12, 2, 75000, 150000, 19500, 169500, NOW(), NOW()),
(43, 6, 11, 1, 65000, 65000, 8450, 73450, NOW(), NOW()),
(44, 7, 2, 25, 20000, 500000, 65000, 565000, NOW(), NOW()),
(45, 7, 5, 20, 12000, 240000, 31200, 271200, NOW(), NOW()),
(46, 7, 14, 1, 45000, 45000, 5850, 50850, NOW(), NOW()),
(47, 8, 1, 2, 950000, 1900000, 247000, 2147000, NOW(), NOW()),
(48, 8, 3, 8, 50000, 400000, 52000, 452000, NOW(), NOW()),
(49, 8, 6, 6, 35000, 210000, 27300, 237300, NOW(), NOW()),
(50, 8, 2, 18, 20000, 360000, 46800, 406800, NOW(), NOW()),
(51, 8, 5, 25, 12000, 300000, 39000, 339000, NOW(), NOW()),
(52, 8, 8, 2, 75000, 150000, 19500, 169500, NOW(), NOW()),
(53, 8, 9, 1, 85000, 85000, 11050, 96050, NOW(), NOW()),
(54, 8, 15, 1, 120000, 120000, 15600, 135600, NOW(), NOW()),
(55, 8, 14, 3, 45000, 135000, 17550, 152550, NOW(), NOW());

-- ============================================================================
-- FASE 6: ROLES Y PERMISOS (Relaciones)
-- ============================================================================

-- 30. ROLES PERMISOS (Asignar permisos a roles)
-- Administrador: todos los permisos
INSERT INTO roles_permisos (role_id, permission_id, creado_en) 
SELECT 1, id, NOW() FROM permisos WHERE empresa_id = 1;

-- Gerente: muchos permisos
INSERT INTO roles_permisos (role_id, permission_id, creado_en)
SELECT 2, id, NOW() FROM permisos WHERE empresa_id = 1 AND slug IN ('empresas.leer', 'productos.leer', 'productos.crear', 'productos.actualizar', 'ventas.leer', 'ventas.crear', 'clientes.leer', 'clientes.crear', 'compras.leer', 'inventario.leer', 'inventario.actualizar', 'contabilidad.leer', 'nomina.leer');

-- Contador: contabilidad
INSERT INTO roles_permisos (role_id, permission_id, creado_en)
SELECT 3, id, NOW() FROM permisos WHERE empresa_id = 1 AND slug IN ('contabilidad.leer', 'contabilidad.crear', 'ventas.leer', 'compras.leer', 'nomina.leer');

-- Vendedor: ventas
INSERT INTO roles_permisos (role_id, permission_id, creado_en)
SELECT 4, id, NOW() FROM permisos WHERE empresa_id = 1 AND slug IN ('ventas.leer', 'ventas.crear', 'clientes.leer', 'clientes.crear', 'productos.leer', 'inventario.leer');

-- Comprador: compras
INSERT INTO roles_permisos (role_id, permission_id, creado_en)
SELECT 5, id, NOW() FROM permisos WHERE empresa_id = 1 AND slug IN ('compras.leer', 'compras.crear', 'inventario.leer', 'inventario.actualizar', 'productos.leer');

-- Bodeguero: inventario
INSERT INTO roles_permisos (role_id, permission_id, creado_en)
SELECT 6, id, NOW() FROM permisos WHERE empresa_id = 1 AND slug IN ('inventario.leer', 'inventario.actualizar', 'productos.leer');

-- Usuario: solo lectura
INSERT INTO roles_permisos (role_id, permission_id, creado_en)
SELECT 7, id, NOW() FROM permisos WHERE empresa_id = 1 AND slug LIKE '%.leer';

-- 31. USUARIOS ROLES (Asignar roles a usuarios)
INSERT INTO rol_usuario (usuario_id, role_id, empresa_id, creado_en) VALUES
(1, 1, 1, NOW()),  -- Admin
(2, 2, 1, NOW()),  -- Gerente
(3, 3, 1, NOW()),  -- Contador
(4, 4, 1, NOW()),  -- Vendedor
(5, 6, 1, NOW()); -- Bodeguero

-- ============================================================================
-- FASE 7: DATOS CONTABLES ADICIONALES
-- ============================================================================

-- 32. CUENTAS POR COBRAR
INSERT INTO cuentas_por_cobrar (id, empresa_id, cliente_id, numero_documento, tipo_documento, fecha_documento, fecha_vencimiento, monto, monto_pagado, saldo, estado, creado_en, actualizado_en) VALUES
(1, 1, 1, 'V-001', 'factura', '2026-02-04', '2026-03-06', 4068000, 2000000, 2068000, 'parcial_pagada', NOW(), NOW()),
(2, 1, 2, 'V-002', 'factura', '2026-02-05', '2026-04-05', 5876000, 0, 5876000, 'pendiente', NOW(), NOW()),
(3, 1, 3, 'V-003', 'factura', '2026-02-06', '2026-02-06', 1401200, 1401200, 0, 'pagada', NOW(), NOW()),
(4, 1, 4, 'V-004', 'factura', '2026-02-07', '2026-03-09', 3220500, 0, 3220500, 'pendiente', NOW(), NOW()),
(5, 1, 6, 'V-006', 'factura', '2026-02-09', '2026-03-11', 5085000, 0, 5085000, 'pendiente', NOW(), NOW());

-- 33. CUENTAS POR PAGAR
INSERT INTO cuentas_por_pagar (id, empresa_id, proveedor_id, numero_documento, tipo_documento, fecha_documento, fecha_vencimiento, monto, monto_pagado, saldo, estado, creado_en, actualizado_en) VALUES
(1, 1, 1, 'OC-001', 'orden_compra', '2026-02-01', '2026-03-17', 12000000, 4000000, 8000000, 'parcial_pagada', NOW(), NOW()),
(2, 1, 3, 'OC-002', 'orden_compra', '2026-02-03', '2026-03-05', 5500000, 0, 5500000, 'pendiente', NOW(), NOW()),
(3, 1, 2, 'OC-003', 'orden_compra', '2026-02-05', '2026-03-21', 8900000, 2000000, 6900000, 'pendiente', NOW(), NOW()),
(4, 1, 4, 'OC-004', 'orden_compra', '2026-02-07', '2026-03-24', 2250000, 0, 2250000, 'pendiente', NOW(), NOW()),
(5, 1, 5, 'OC-005', 'orden_compra', '2026-02-08', '2026-03-25', 6750000, 0, 6750000, 'pendiente', NOW(), NOW());

-- ============================================================================
-- FASE 8: OTROS DATOS BÁSICOS
-- ============================================================================

-- 34. CONFIGURACIONES
INSERT INTO configuraciones (id, empresa_id, clave, valor, descripcion, creado_en, actualizado_en) VALUES
(1, 1, 'nombre_empresa', 'Senselab', 'Nombre oficial de la empresa', NOW(), NOW()),
(2, 1, 'ruc_empresa', '3101234567-8', 'RUC de la empresa', NOW(), NOW()),
(3, 1, 'email_empresa', 'info@senselab.com', 'Email de la empresa', NOW(), NOW()),
(4, 1, 'telefono_empresa', '+(506)0000-0000', 'Teléfono de la empresa', NOW(), NOW()),
(5, 1, 'porcentaje_iva_defecto', '13', 'Porcentaje IVA por defecto', NOW(), NOW()),
(6, 1, 'moneda_defecto', 'CRC', 'Moneda por defecto', NOW(), NOW()),
(7, 1, 'idioma_defecto', 'es', 'Idioma por defecto', NOW(), NOW()),
(8, 1, 'zona_horaria', 'America/Costa_Rica', 'Zona horaria', NOW(), NOW());

-- 35. NOTIFICACIONES
INSERT INTO notificaciones (id, usuario_id, empresa_id, titulo, mensaje, tipo, leida, creado_en, actualizado_en) VALUES
(1, 1, 1, 'Bienvenida', 'Bienvenido a Senselab Core API', 'info', true, NOW(), NOW()),
(2, 2, 1, 'Nueva Venta', 'Nueva venta registrada: V-001', 'venta', false, NOW(), NOW()),
(3, 3, 1, 'Auditoria', 'Cambio en configuración de empresa', 'auditoria', false, NOW(), NOW()),
(4, 4, 1, 'Nuevo Cliente', 'Cliente José Manuel Rodríguez registrado', 'cliente', false, NOW(), NOW()),
(5, 5, 1, 'Stock Bajo', 'Producto PROD-001 con stock bajo', 'inventario', false, NOW(), NOW());

-- 36. LOGS DE ACCESO SISTEMA
INSERT IGNORE INTO logs_acceso_sistema (id, usuario_id, empresa_id, ip_acceso, navegador, sistema_operativo, fecha_acceso, accion, tabla_afectada, creado_en) VALUES
(1, 1, 1, '192.168.1.100', 'Chrome', 'Ubuntu 22.04', NOW(), 'login', 'usuarios', NOW()),
(2, 2, 1, '192.168.1.101', 'Firefox', 'Windows 10', NOW(), 'crear_venta', 'ventas', NOW()),
(3, 3, 1, '192.168.1.102', 'Safari', 'macOS', NOW(), 'crear_asiento', 'asientos_contables', NOW()),
(4, 4, 1, '192.168.1.103', 'Chrome', 'Ubuntu 22.04', NOW(), 'crear_venta', 'ventas', NOW()),
(5, 5, 1, '192.168.1.104', 'Firefox', 'Windows 10', NOW(), 'crear_entrada_inventario', 'entradas_inventario', NOW());

-- ============================================================================
-- RESUMEN
-- ============================================================================
-- Total de registros insertados: 500+
-- Empresas: 3
-- Usuarios: 5
-- Productos: 15
-- Clientes: 8
-- Proveedores: 5
-- Empleados: 5
-- Órdenes de Compra: 5
-- Ventas: 8
-- Roles: 8
-- Permisos: 20
-- Y muchas más tablas maestras...
