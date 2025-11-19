-- phpMyAdmin SQL Dump
-- version 5.2.2deb2
-- https://www.phpmyadmin.net/
--
-- Servidor: localhost:3306
-- Tiempo de generación: 05-11-2025 a las 02:35:02
-- Versión del servidor: 8.4.6-0ubuntu3
-- Versión de PHP: 8.4.11

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: api_db
--
CREATE DATABASE IF NOT EXISTS api_db DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE api_db;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla almacenes
--
-- Creación: 05-11-2025 a las 02:15:01
--

DROP TABLE IF EXISTS almacenes;
CREATE TABLE almacenes (
  id int UNSIGNED NOT NULL,
  empresa_id int UNSIGNED NOT NULL,
  nombre varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  sucursal_id int UNSIGNED DEFAULT NULL,
  descripcion text COLLATE utf8mb4_unicode_ci,
  direccion text COLLATE utf8mb4_unicode_ci,
  activo tinyint(1) DEFAULT '1',
  eliminado tinyint(1) DEFAULT '0',
  creado_en timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  actualizado_en timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Ubicaciones físicas donde se guarda el stock (bodegas, camiones, etc.). Central para el control de stock.';

--
-- RELACIONES PARA LA TABLA almacenes:
--   sucursal_id
--       sucursales -> id
--   empresa_id
--       empresas -> id
--

--
-- Volcado de datos para la tabla almacenes
--

INSERT INTO almacenes (id, empresa_id, nombre, sucursal_id, descripcion, direccion, activo, eliminado, creado_en, actualizado_en) VALUES
(1, 1, 'Almacén de Activos Fijos', 1, NULL, NULL, 1, 0, '2025-10-25 04:27:41', '2025-10-25 04:27:41'),
(2, 2, 'Inventario de Repuestos Bus', 2, NULL, NULL, 1, 0, '2025-10-25 04:27:41', '2025-10-25 04:27:41'),
(3, 3, 'Inventario de Insumos Tinta', 3, NULL, NULL, 1, 0, '2025-10-25 04:27:41', '2025-10-25 04:27:41');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla asientos_contables
--
-- Creación: 05-11-2025 a las 02:15:01
--

DROP TABLE IF EXISTS asientos_contables;
CREATE TABLE asientos_contables (
  id int UNSIGNED NOT NULL,
  empresa_id int UNSIGNED NOT NULL,
  fecha datetime NOT NULL,
  descripcion text COLLATE utf8mb4_unicode_ci,
  total_debe decimal(18,5) NOT NULL,
  total_haber decimal(18,5) NOT NULL,
  estado varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  activo tinyint(1) DEFAULT '1',
  eliminado tinyint(1) DEFAULT '0',
  creado_en timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  actualizado_en timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Registro de cada movimiento contable (debe/haber) que afecta a las cuentas.';

--
-- RELACIONES PARA LA TABLA asientos_contables:
--   empresa_id
--       empresas -> id
--

--
-- Volcado de datos para la tabla asientos_contables
--

INSERT INTO asientos_contables (id, empresa_id, fecha, descripcion, total_debe, total_haber, estado, activo, eliminado, creado_en, actualizado_en) VALUES
(1, 1, '2025-10-05 10:00:00', 'Venta 1: Servicios de Desarrollo', 678000.00000, 678000.00000, 'Mayorizado', 1, 0, '2025-10-25 04:27:41', '2025-10-25 04:27:41');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla buses_unidades
--
-- Creación: 05-11-2025 a las 02:15:01
--

DROP TABLE IF EXISTS buses_unidades;
CREATE TABLE buses_unidades (
  id int UNSIGNED NOT NULL,
  empresa_id int UNSIGNED NOT NULL,
  placa varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Placa o matrícula del vehículo',
  modelo_id int UNSIGNED DEFAULT NULL,
  capacidad_asientos int NOT NULL,
  identificador_interno varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Ej: Número de flota 101',
  activo tinyint(1) DEFAULT '1',
  eliminado tinyint(1) DEFAULT '0',
  creado_en timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  actualizado_en timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Registro de las unidades de transporte físico (activos con placa, año, empresa). Enlaza a modelos_buses.';

--
-- RELACIONES PARA LA TABLA buses_unidades:
--   empresa_id
--       empresas -> id
--   modelo_id
--       modelos_buses -> id
--

--
-- Volcado de datos para la tabla buses_unidades
--

INSERT INTO buses_unidades (id, empresa_id, placa, modelo_id, capacidad_asientos, identificador_interno, activo, eliminado, creado_en, actualizado_en) VALUES
(1, 2, 'SJ-4589', 1, 50, '101', 1, 0, '2025-10-25 04:43:30', '2025-11-05 02:02:47'),
(2, 2, 'SJ-7789', 2, 45, '102', 1, 0, '2025-10-25 04:47:06', '2025-11-05 02:02:47'),
(4, 1, 'SJ-7787', 2, 45, '102', 1, 0, '2025-10-25 04:47:06', '2025-11-05 02:02:47');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla cabys
--
-- Creación: 05-11-2025 a las 02:15:01
--

DROP TABLE IF EXISTS cabys;
CREATE TABLE cabys (
  id int UNSIGNED NOT NULL,
  codigo varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '13 digitos(hacienda C.R)',
  descripcion text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  impuesto_iva_predeterminado decimal(5,2) DEFAULT NULL COMMENT 'Tasa IVA predeterminada para este CAByS (ej: 13.00, 4.00, 0.00)',
  activo tinyint(1) NOT NULL DEFAULT '1',
  eliminado tinyint(1) NOT NULL DEFAULT '0',
  creado_en timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  actualizado_en timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Catálogo de Bienes y Servicios (CAByS) de Costa Rica. Usado para clasificación fiscal de productos.';

--
-- RELACIONES PARA LA TABLA cabys:
--

--
-- Volcado de datos para la tabla cabys
--

INSERT INTO cabys (id, codigo, descripcion, impuesto_iva_predeterminado, activo, eliminado, creado_en, actualizado_en) VALUES
(1, '6201100100000', 'Servicios de desarrollo de software', NULL, 1, 0, '2025-10-25 04:27:41', '2025-10-25 04:27:41'),
(2, '9602004223000', 'Servicios de tatuajes', NULL, 1, 0, '2025-10-25 04:27:41', '2025-10-29 02:59:23'),
(3, '4921019483000', 'Transporte de pasajeros regular', NULL, 1, 0, '2025-10-25 04:27:41', '2025-10-29 02:59:37'),
(4, '5010100000000', 'Suministros de Oficina', NULL, 1, 0, '2025-10-25 04:27:41', '2025-10-25 04:27:41');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla cajas
--
-- Creación: 05-11-2025 a las 00:33:04
--

DROP TABLE IF EXISTS cajas;
CREATE TABLE cajas (
  id int UNSIGNED NOT NULL,
  sucursal_id int UNSIGNED DEFAULT NULL,
  nombre varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  descripcion text COLLATE utf8mb4_unicode_ci,
  activo tinyint(1) DEFAULT '1',
  eliminado tinyint(1) DEFAULT '0',
  creado_en timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  actualizado_en timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- RELACIONES PARA LA TABLA cajas:
--   sucursal_id
--       sucursales -> id
--

--
-- Volcado de datos para la tabla cajas
--

INSERT INTO cajas (id, sucursal_id, nombre, descripcion, activo, eliminado, creado_en, actualizado_en) VALUES
(1, 1, 'Caja Principal 1', 'Caja para operaciones principales de la sucursal', 1, 0, '2025-05-19 06:07:39', '2025-05-19 06:07:39'),
(2, 1, 'Caja Secundaria 2', 'Caja auxiliar para pagos rápidos', 1, 0, '2025-05-19 06:07:39', '2025-05-19 06:07:39'),
(3, 2, 'Caja Central Este 1', 'Caja principal de la sucursal Este', 1, 0, '2025-05-19 06:07:39', '2025-05-19 06:07:39'),
(4, 3, 'Caja Almacén 1', 'Caja para manejo de efectivo en almacén', 1, 0, '2025-05-19 06:07:39', '2025-05-19 06:07:39'),
(5, NULL, 'Caja Sur 1', 'Caja de la oficina sur', 1, 0, '2025-05-19 06:07:39', '2025-11-05 00:32:13');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla caja_chica
--
-- Creación: 01-11-2025 a las 22:41:16
--

DROP TABLE IF EXISTS caja_chica;
CREATE TABLE caja_chica (
  id int UNSIGNED NOT NULL,
  empresa_id int UNSIGNED NOT NULL,
  fecha datetime NOT NULL,
  descripcion text COLLATE utf8mb4_unicode_ci,
  monto decimal(10,2) NOT NULL,
  tipo varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  responsable_id int UNSIGNED NOT NULL,
  activo tinyint(1) DEFAULT '1',
  eliminado tinyint(1) DEFAULT '0',
  creado_en timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  actualizado_en timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- RELACIONES PARA LA TABLA caja_chica:
--   empresa_id
--       empresas -> id
--   responsable_id
--       usuarios -> id
--

--
-- Volcado de datos para la tabla caja_chica
--

INSERT INTO caja_chica (id, empresa_id, fecha, descripcion, monto, tipo, responsable_id, activo, eliminado, creado_en, actualizado_en) VALUES
(1, 1, '2024-07-28 09:00:00', 'Compra de suministros de oficina', 50.00, 'Egreso', 1, 1, 0, '2025-05-19 06:55:08', '2025-05-19 06:55:08'),
(2, 1, '2024-07-28 15:30:00', 'Pago de taxi a cliente', 20.00, 'Egreso', 3, 1, 0, '2025-05-19 06:55:08', '2025-05-19 06:55:08'),
(3, 1, '2024-07-29 11:00:00', 'Ingreso por venta menor', 100.00, 'Ingreso', 3, 1, 0, '2025-05-19 06:55:08', '2025-05-19 06:55:08'),
(4, 1, '2024-07-29 16:45:00', 'Compra de café y galletas', 10.00, 'Egreso', 1, 1, 0, '2025-05-19 06:55:08', '2025-05-19 06:55:08'),
(5, 1, '2024-07-30 10:30:00', 'Reembolso de viáticos', 30.00, 'Ingreso', 2, 1, 0, '2025-05-19 06:55:08', '2025-05-19 06:55:08');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla cargos
--
-- Creación: 01-11-2025 a las 21:49:31
--

DROP TABLE IF EXISTS cargos;
CREATE TABLE cargos (
  id int UNSIGNED NOT NULL,
  nombre varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  descripcion text COLLATE utf8mb4_unicode_ci,
  activo tinyint(1) DEFAULT '1',
  eliminado tinyint(1) DEFAULT '0',
  creado_en timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  actualizado_en timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- RELACIONES PARA LA TABLA cargos:
--

--
-- Volcado de datos para la tabla cargos
--

INSERT INTO cargos (id, nombre, descripcion, activo, eliminado, creado_en, actualizado_en) VALUES
(1, 'Gerente General', 'Responsable de la dirección general de la empresa', 1, 0, '2025-05-22 07:52:20', '2025-05-22 07:52:20'),
(2, 'Vendedor Senior', 'Encargado de ventas y atención a clientes clave', 1, 0, '2025-05-22 07:52:20', '2025-05-22 07:52:20'),
(3, 'Contador Principal', 'Responsable de la contabilidad y finanzas', 1, 0, '2025-05-22 07:52:20', '2025-05-22 07:52:20'),
(4, 'Asistente Administrativo', 'Apoyo en tareas administrativas y de oficina', 1, 0, '2025-05-22 07:52:20', '2025-05-22 07:52:20'),
(5, 'Soporte Técnico Nivel 1', 'Atención de primer nivel para incidencias técnicas', 1, 0, '2025-05-22 07:52:20', '2025-05-22 07:52:20'),
(6, 'Jefe de Almacén', 'Responsable de la gestión y control de inventario en el almacén', 1, 0, '2025-05-22 07:52:20', '2025-05-22 07:52:20'),
(7, 'Analista de Marketing', 'Encargado de estrategias de marketing y análisis de mercado', 1, 0, '2025-05-22 07:52:20', '2025-05-22 07:52:20');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla categorias_productos
--
-- Creación: 05-11-2025 a las 00:39:39
--

DROP TABLE IF EXISTS categorias_productos;
CREATE TABLE categorias_productos (
  id int UNSIGNED NOT NULL,
  empresa_id int UNSIGNED NOT NULL,
  nombre varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  descripcion text COLLATE utf8mb4_unicode_ci,
  activo tinyint(1) DEFAULT '1',
  eliminado tinyint(1) DEFAULT '0',
  creado_en timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  actualizado_en timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- RELACIONES PARA LA TABLA categorias_productos:
--

--
-- Volcado de datos para la tabla categorias_productos
--

INSERT INTO categorias_productos (id, empresa_id, nombre, descripcion, activo, eliminado, creado_en, actualizado_en) VALUES
(1, 1, 'Electrónicos', 'Productos electrónicos de consumo', 1, 0, '2025-05-19 06:06:52', '2025-05-19 06:06:52'),
(2, 1, 'Ropa', 'Prendas de vestir para todas las edades', 1, 0, '2025-05-19 06:06:52', '2025-05-19 06:06:52'),
(3, 2, 'Alimentos', 'Productos alimenticios y bebidas', 1, 0, '2025-05-19 06:06:52', '2025-05-19 06:06:52'),
(4, 2, 'Hogar', 'Artículos para el hogar y decoración', 1, 0, '2025-05-19 06:06:52', '2025-05-19 06:06:52'),
(5, 3, 'Libros', 'Publicaciones literarias y educativas', 1, 0, '2025-05-19 06:06:52', '2025-05-19 06:06:52');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla clientes
--
-- Creación: 03-11-2025 a las 00:10:23
--

DROP TABLE IF EXISTS clientes;
CREATE TABLE clientes (
  id int UNSIGNED NOT NULL,
  empresa_id int UNSIGNED NOT NULL,
  nombre varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  apellido varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  tipo_documento_interno varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Tipo de documento interno del sistema',
  num_identificacion_dgt varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Número de identificación del cliente (Cédula, NITE, etc.)',
  tipo_identificacion_dgt varchar(2) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Código DGT: 01, 02, 03, 04, 05, 06, 07 (CRÍTICO)',
  telefono varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  email varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  actividad_economica_dgt varchar(6) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Actividad económica si es contribuyente (v. 4.4)',
  direccion text COLLATE utf8mb4_unicode_ci,
  activo tinyint(1) DEFAULT '1',
  eliminado tinyint(1) DEFAULT '0',
  creado_en timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  actualizado_en timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- RELACIONES PARA LA TABLA clientes:
--

--
-- Volcado de datos para la tabla clientes
--

INSERT INTO clientes (id, empresa_id, nombre, apellido, tipo_documento_interno, num_identificacion_dgt, tipo_identificacion_dgt, telefono, email, actividad_economica_dgt, direccion, activo, eliminado, creado_en, actualizado_en) VALUES
(1, 1, 'Cliente Tech CR', '(Física)', NULL, '101230456', '01', NULL, NULL, NULL, NULL, 1, 0, '2025-10-25 04:27:41', '2025-10-25 04:27:41'),
(2, 1, 'Empresa Grande', 'S.A.', NULL, '3001002003', '02', NULL, NULL, NULL, NULL, 1, 0, '2025-10-25 04:27:41', '2025-10-25 04:27:41'),
(3, 2, 'Turista', 'Extranjero', NULL, '123456789012', '06', NULL, NULL, NULL, NULL, 1, 0, '2025-10-25 04:27:41', '2025-10-25 04:27:41'),
(4, 1, 'Inversiones Globales', 'S.A.', NULL, '3101001004', '02', NULL, 'contacto@invglobal.com', NULL, NULL, 1, 0, '2025-10-26 02:15:28', '2025-10-26 02:15:28'),
(5, 2, 'Pasajero Frecuente', 'Nacional', NULL, '108880777', '01', NULL, NULL, NULL, NULL, 1, 0, '2025-10-26 02:15:28', '2025-10-26 02:15:28'),
(6, 3, 'Cliente Arte', 'Digital', NULL, '404440555', '01', NULL, NULL, NULL, NULL, 1, 0, '2025-10-26 02:15:28', '2025-10-26 02:15:28');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla comprobantes_recibidos_electronicos
--
-- Creación: 05-11-2025 a las 00:27:08
--

DROP TABLE IF EXISTS comprobantes_recibidos_electronicos;
CREATE TABLE comprobantes_recibidos_electronicos (
  id bigint UNSIGNED NOT NULL,
  empresa_id int UNSIGNED NOT NULL,
  proveedor_id int UNSIGNED DEFAULT NULL,
  clave_numerica varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Clave numérica del comprobante electrónico (50 dígitos)',
  consecutivo_receptor varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Consecutivo interno del receptor',
  tipo_documento_dgt varchar(2) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Código del tipo de documento según DGT (ej: 01, 02, 03, 04, 08, 09)',
  fecha_emision_comprobante datetime NOT NULL,
  fecha_recepcion_sistema datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  moneda char(3) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  total_impuesto decimal(18,5) DEFAULT NULL,
  total_comprobante decimal(18,5) NOT NULL,
  xml_contenido longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  xml_respuesta_hacienda longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  estado_hacienda varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Ej: Aceptado, Rechazado, Procesando',
  mensaje_hacienda text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  fecha_respuesta_hacienda datetime DEFAULT NULL,
  confirmado_usuario tinyint(1) NOT NULL DEFAULT '0' COMMENT '0: Pendiente, 1: Confirmado/Aceptado, 2: Rechazado por usuario',
  fecha_confirmacion_usuario datetime DEFAULT NULL,
  usuario_confirmacion_id int UNSIGNED DEFAULT NULL,
  entrada_inventario_id int DEFAULT NULL COMMENT 'Enlace a la entrada de inventario si aplica',
  activo tinyint(1) NOT NULL DEFAULT '1',
  eliminado tinyint(1) NOT NULL DEFAULT '0',
  creado_en timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  actualizado_en timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Almacena comprobantes electrónicos recibidos de proveedores';

--
-- RELACIONES PARA LA TABLA comprobantes_recibidos_electronicos:
--   empresa_id
--       empresas -> id
--   entrada_inventario_id
--       entradas_inventario -> id
--   proveedor_id
--       proveedores -> id
--   usuario_confirmacion_id
--       usuarios -> id
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla configuraciones
--
-- Creación: 05-11-2025 a las 00:27:37
--

DROP TABLE IF EXISTS configuraciones;
CREATE TABLE configuraciones (
  id int UNSIGNED NOT NULL,
  empresa_id int UNSIGNED NOT NULL,
  clave varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  valor text COLLATE utf8mb4_unicode_ci NOT NULL,
  tipo_dato varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  descripcion text COLLATE utf8mb4_unicode_ci,
  activo tinyint(1) DEFAULT '1',
  eliminado tinyint(1) DEFAULT '0',
  creado_en timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  actualizado_en timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- RELACIONES PARA LA TABLA configuraciones:
--

--
-- Volcado de datos para la tabla configuraciones
--

INSERT INTO configuraciones (id, empresa_id, clave, valor, tipo_dato, descripcion, activo, eliminado, creado_en, actualizado_en) VALUES
(1, 1, 'Moneda', 'CRC', 'texto', 'Moneda por defecto del sistema', 1, 0, '2025-05-19 06:50:47', '2025-05-27 20:21:37'),
(2, 1, 'IVA', '0.12', 'numero', 'Tasa de Impuesto al Valor Agregado', 1, 0, '2025-05-19 06:50:47', '2025-05-24 04:52:55'),
(3, 1, 'FormatoFecha', 'YYYY-MM-DD', 'texto', 'Formato de fecha utilizado en el sistema', 1, 0, '2025-05-19 06:50:47', '2025-05-24 04:52:55'),
(4, 2, 'Idioma', 'es', 'texto', 'Idioma por defecto del sistema', 1, 0, '2025-05-19 06:50:47', '2025-05-24 04:52:55'),
(5, 2, 'Tema', 'oscuro', 'texto', 'Tema de la interfaz de usuario', 1, 0, '2025-05-19 06:50:47', '2025-05-24 04:52:55'),
(6, 1, 'MonedaSecundaria', 'CRC', 'texto', 'Colón Costarricense', 1, 0, '2025-05-19 06:53:46', '2025-08-19 04:31:31');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla consecutivos_fe
--
-- Creación: 05-11-2025 a las 02:15:01
--

DROP TABLE IF EXISTS consecutivos_fe;
CREATE TABLE consecutivos_fe (
  id int UNSIGNED NOT NULL,
  empresa_id int UNSIGNED NOT NULL,
  sucursal_id int UNSIGNED DEFAULT NULL,
  tipo_documento_dgt varchar(2) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Código DGT: 01, 04, 03, etc.',
  prefijo varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'El rango DGT (ej: 001, 002, 003)',
  consecutivo_actual int NOT NULL DEFAULT '1' COMMENT 'El último número usado dentro del rango',
  estado varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Activo' COMMENT 'Activo, Agotado, Inactivo',
  fecha_autorizacion date DEFAULT NULL,
  activo tinyint(1) DEFAULT '1',
  eliminado tinyint(1) DEFAULT '0',
  creado_en timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  actualizado_en timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Maneja la secuencia numérica de los comprobantes electrónicos (clave, consecutivo, tipo). Requisito de Hacienda.';

--
-- RELACIONES PARA LA TABLA consecutivos_fe:
--

--
-- Volcado de datos para la tabla consecutivos_fe
--

INSERT INTO consecutivos_fe (id, empresa_id, sucursal_id, tipo_documento_dgt, prefijo, consecutivo_actual, estado, fecha_autorizacion, activo, eliminado, creado_en, actualizado_en) VALUES
(1, 1, NULL, '01', '001', 1, 'Activo', NULL, 1, 0, '2025-10-25 04:27:41', '2025-10-25 04:27:41'),
(2, 2, NULL, '04', '001', 1, 'Activo', NULL, 1, 0, '2025-10-25 04:27:41', '2025-10-25 04:27:41');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla cuentas_contables
--
-- Creación: 05-11-2025 a las 02:15:01
--

DROP TABLE IF EXISTS cuentas_contables;
CREATE TABLE cuentas_contables (
  id int UNSIGNED NOT NULL,
  empresa_id int UNSIGNED NOT NULL,
  nombre varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  descripcion text COLLATE utf8mb4_unicode_ci,
  codigo varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  tipo_cuenta_id int UNSIGNED DEFAULT NULL,
  cuenta_padre_id int UNSIGNED DEFAULT NULL,
  permite_movimientos tinyint(1) DEFAULT '1',
  saldo_actual decimal(15,2) DEFAULT '0.00',
  activo tinyint(1) DEFAULT '1',
  eliminado tinyint(1) DEFAULT '0',
  creado_en timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  actualizado_en timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='El plan de cuentas (PUC) de la empresa. Estructura jerárquica para registrar asientos.';

--
-- RELACIONES PARA LA TABLA cuentas_contables:
--   cuenta_padre_id
--       cuentas_contables -> id
--   cuenta_padre_id
--       cuentas_contables -> id
--   tipo_cuenta_id
--       tipos_cuentas -> id
--   empresa_id
--       empresas -> id
--

--
-- Volcado de datos para la tabla cuentas_contables
--

INSERT INTO cuentas_contables (id, empresa_id, nombre, descripcion, codigo, tipo_cuenta_id, cuenta_padre_id, permite_movimientos, saldo_actual, activo, eliminado, creado_en, actualizado_en) VALUES
(101, 1, 'Caja - Colones', NULL, '110101', NULL, NULL, 1, 0.00, 1, 0, '2025-10-25 04:27:41', '2025-10-25 04:27:41'),
(102, 1, 'Bancos - Colones', NULL, '110102', NULL, NULL, 1, 0.00, 1, 0, '2025-10-25 04:27:41', '2025-10-25 04:27:41'),
(201, 1, 'Ventas de Servicios (CR)', NULL, '410101', NULL, NULL, 1, 0.00, 1, 0, '2025-10-25 04:27:41', '2025-10-25 04:27:41'),
(202, 1, 'Ventas de Productos (CR)', NULL, '410102', NULL, NULL, 1, 0.00, 1, 0, '2025-10-25 04:27:41', '2025-10-25 04:27:41'),
(301, 1, 'IVA por Pagar', NULL, '210101', NULL, NULL, 1, 0.00, 1, 0, '2025-10-25 04:27:41', '2025-10-25 04:27:41'),
(302, 1, 'Costo de Venta', NULL, '510101', NULL, NULL, 1, 0.00, 1, 0, '2025-10-25 04:27:41', '2025-10-25 04:27:41');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla cuentas_por_cobrar
--
-- Creación: 05-11-2025 a las 02:15:01
--

DROP TABLE IF EXISTS cuentas_por_cobrar;
CREATE TABLE cuentas_por_cobrar (
  id bigint UNSIGNED NOT NULL,
  empresa_id int UNSIGNED NOT NULL,
  cliente_id int UNSIGNED DEFAULT NULL,
  venta_id int UNSIGNED DEFAULT NULL,
  documento_referencia varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Ej: Número de Factura de la venta',
  fecha_emision date NOT NULL COMMENT 'Fecha de emisión de la factura/cuenta por cobrar',
  fecha_vencimiento date NOT NULL COMMENT 'Fecha de vencimiento para el pago',
  moneda char(3) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  monto_original decimal(18,5) NOT NULL,
  monto_pagado decimal(18,5) NOT NULL DEFAULT '0.00000',
  saldo_pendiente decimal(15,2) GENERATED ALWAYS AS ((monto_original - monto_pagado)) STORED COMMENT 'Monto original - Monto pagado. Calculado automáticamente.',
  estado varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Pendiente' COMMENT 'Ej: Pendiente, Pagada Parcialmente, Pagada Totalmente, Vencida, Anulada',
  observaciones text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  activo tinyint(1) NOT NULL DEFAULT '1',
  eliminado tinyint(1) NOT NULL DEFAULT '0',
  creado_en timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  actualizado_en timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Documentos y saldos pendientes de cobro a clientes. Generado por ventas a crédito.';

--
-- RELACIONES PARA LA TABLA cuentas_por_cobrar:
--   cliente_id
--       clientes -> id
--   venta_id
--       ventas -> id
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla cuentas_por_pagar
--
-- Creación: 05-11-2025 a las 02:15:01
--

DROP TABLE IF EXISTS cuentas_por_pagar;
CREATE TABLE cuentas_por_pagar (
  id bigint UNSIGNED NOT NULL,
  empresa_id int UNSIGNED NOT NULL,
  proveedor_id int NOT NULL,
  orden_compra_id int DEFAULT NULL COMMENT 'Referencia a la OC si aplica',
  comprobante_recibido_id bigint UNSIGNED DEFAULT NULL COMMENT 'Referencia al comprobante electrónico recibido si aplica',
  documento_referencia_proveedor varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Número de factura del proveedor',
  fecha_emision_documento date NOT NULL COMMENT 'Fecha de la factura del proveedor',
  fecha_recepcion_documento date DEFAULT NULL COMMENT 'Fecha en que se registró la factura del proveedor en el sistema',
  fecha_vencimiento date NOT NULL COMMENT 'Fecha de vencimiento para el pago al proveedor',
  moneda char(3) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  monto_original decimal(18,5) NOT NULL,
  monto_pagado decimal(18,5) NOT NULL DEFAULT '0.00000',
  saldo_pendiente decimal(15,2) GENERATED ALWAYS AS ((monto_original - monto_pagado)) STORED COMMENT 'Monto original - Monto pagado. Calculado automáticamente.',
  estado varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Pendiente' COMMENT 'Ej: Pendiente, Pagada Parcialmente, Pagada Totalmente, Vencida, Anulada',
  observaciones text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  activo tinyint(1) NOT NULL DEFAULT '1',
  eliminado tinyint(1) NOT NULL DEFAULT '0',
  creado_en timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  actualizado_en timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Documentos y saldos pendientes de pago a proveedores/acreedores.';

--
-- RELACIONES PARA LA TABLA cuentas_por_pagar:
--

--
-- Volcado de datos para la tabla cuentas_por_pagar
--

INSERT INTO cuentas_por_pagar (id, empresa_id, proveedor_id, orden_compra_id, comprobante_recibido_id, documento_referencia_proveedor, fecha_emision_documento, fecha_recepcion_documento, fecha_vencimiento, moneda, monto_original, monto_pagado, estado, observaciones, activo, eliminado, creado_en, actualizado_en) VALUES
(1, 1, 1, NULL, 1, '50601012400012345678901000010100000000011234567890', '2025-05-20', '2025-05-27', '2025-06-04', 'CRC', 113000.00000, 50000.00000, 'Pagada Parcialmente', 'Generado desde comprobante electrónico recibido y aceptado', 1, 0, '2025-05-27 20:46:58', '2025-05-27 21:00:17');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla detalle_asientos
--
-- Creación: 04-11-2025 a las 21:28:29
--

DROP TABLE IF EXISTS detalle_asientos;
CREATE TABLE detalle_asientos (
  id int NOT NULL,
  asiento_contable_id int NOT NULL,
  cuenta_contable_id int NOT NULL,
  debe decimal(10,2) NOT NULL,
  haber decimal(10,2) NOT NULL,
  descripcion text COLLATE utf8mb4_unicode_ci,
  activo tinyint(1) DEFAULT '1',
  eliminado tinyint(1) DEFAULT '0',
  creado_en timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  actualizado_en timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- RELACIONES PARA LA TABLA detalle_asientos:
--

--
-- Volcado de datos para la tabla detalle_asientos
--

INSERT INTO detalle_asientos (id, asiento_contable_id, cuenta_contable_id, debe, haber, descripcion, activo, eliminado, creado_en, actualizado_en) VALUES
(1, 1, 102, 678000.00, 0.00, NULL, 1, 0, '2025-10-25 04:27:41', '2025-10-25 04:27:41'),
(2, 1, 201, 0.00, 600000.00, NULL, 1, 0, '2025-10-25 04:27:41', '2025-10-25 04:27:41'),
(3, 1, 301, 0.00, 78000.00, NULL, 1, 0, '2025-10-25 04:27:41', '2025-10-25 04:27:41');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla detalle_entradas_inventario
--
-- Creación: 04-11-2025 a las 21:29:17
--

DROP TABLE IF EXISTS detalle_entradas_inventario;
CREATE TABLE detalle_entradas_inventario (
  id int NOT NULL,
  entrada_inventario_id int NOT NULL,
  producto_id int NOT NULL,
  cantidad decimal(10,2) NOT NULL,
  costo_unitario decimal(12,2) NOT NULL,
  subtotal decimal(12,2) NOT NULL,
  lote varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  fecha_vencimiento date DEFAULT NULL,
  activo tinyint(1) DEFAULT '1',
  eliminado tinyint(1) DEFAULT '0',
  creado_en timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  actualizado_en timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- RELACIONES PARA LA TABLA detalle_entradas_inventario:
--

--
-- Volcado de datos para la tabla detalle_entradas_inventario
--

INSERT INTO detalle_entradas_inventario (id, entrada_inventario_id, producto_id, cantidad, costo_unitario, subtotal, lote, fecha_vencimiento, activo, eliminado, creado_en, actualizado_en) VALUES
(1, 1, 4, 50.00, 7500.00, 375000.00, NULL, NULL, 1, 0, '2025-10-25 04:27:41', '2025-10-25 04:27:41');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla detalle_ordenes_compra
--
-- Creación: 04-11-2025 a las 21:30:54
--

DROP TABLE IF EXISTS detalle_ordenes_compra;
CREATE TABLE detalle_ordenes_compra (
  id int NOT NULL,
  orden_compra_id int NOT NULL,
  producto_id int NOT NULL,
  descripcion_producto text COLLATE utf8mb4_unicode_ci,
  unidad_medida_id int DEFAULT NULL,
  cantidad decimal(10,2) NOT NULL,
  precio_unitario decimal(10,2) NOT NULL,
  subtotal decimal(10,2) NOT NULL,
  cantidad_recibida decimal(10,2) DEFAULT '0.00',
  activo tinyint(1) DEFAULT '1',
  eliminado tinyint(1) DEFAULT '0',
  creado_en timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  actualizado_en timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- RELACIONES PARA LA TABLA detalle_ordenes_compra:
--

--
-- Volcado de datos para la tabla detalle_ordenes_compra
--

INSERT INTO detalle_ordenes_compra (id, orden_compra_id, producto_id, descripcion_producto, unidad_medida_id, cantidad, precio_unitario, subtotal, cantidad_recibida, activo, eliminado, creado_en, actualizado_en) VALUES
(1, 1, 1, 'Laptop X1', 1, 10.00, 100.00, 1000.00, 0.00, 1, 0, '2025-05-19 06:49:09', '2025-05-24 06:07:06'),
(2, 2, 2, 'Smartphone Z2', 1, 5.00, 1000.00, 5000.00, 0.00, 1, 0, '2025-05-19 06:49:09', '2025-05-24 06:07:06'),
(3, 3, 3, 'Arroz Premium', 2, 20.00, 10.00, 200.00, 0.00, 1, 0, '2025-05-19 06:49:09', '2025-05-24 06:07:06'),
(4, 4, 4, 'Sofa Cama', 1, 2.00, 50.00, 100.00, 0.00, 1, 0, '2025-05-19 06:49:09', '2025-05-24 06:07:06'),
(5, 5, 5, 'Cien Años de Soledad', 1, 10.00, 150.00, 1500.00, 0.00, 1, 0, '2025-05-19 06:49:09', '2025-05-24 06:07:06'),
(6, 6, 1, 'Laptop X1 - Pedido especial', 1, 5.00, 1150.00, 5750.00, 0.00, 1, 0, '2025-05-24 05:52:17', '2025-05-24 05:52:17'),
(7, 6, 7, 'Mouse Inalámbrico Ergo - Para stock', 1, 20.00, 20.00, 400.00, 0.00, 1, 0, '2025-05-24 05:52:17', '2025-05-24 05:52:17');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla detalle_presupuestos
--
-- Creación: 04-11-2025 a las 21:32:15
--

DROP TABLE IF EXISTS detalle_presupuestos;
CREATE TABLE detalle_presupuestos (
  id int NOT NULL,
  presupuesto_id int NOT NULL,
  cuenta_contable_id int NOT NULL,
  monto_presupuestado decimal(15,2) NOT NULL,
  activo tinyint(1) DEFAULT '1',
  eliminado tinyint(1) DEFAULT '0',
  creado_en timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  actualizado_en timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Detalle por cuenta de cada presupuesto';

--
-- RELACIONES PARA LA TABLA detalle_presupuestos:
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla detalle_salidas_inventario
--
-- Creación: 04-11-2025 a las 21:33:15
--

DROP TABLE IF EXISTS detalle_salidas_inventario;
CREATE TABLE detalle_salidas_inventario (
  id int NOT NULL,
  salida_inventario_id int NOT NULL,
  producto_id int NOT NULL,
  cantidad decimal(10,2) NOT NULL,
  costo_unitario_salida decimal(12,2) NOT NULL,
  subtotal decimal(12,2) NOT NULL,
  lote varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  fecha_vencimiento date DEFAULT NULL,
  activo tinyint(1) DEFAULT '1',
  eliminado tinyint(1) DEFAULT '0',
  creado_en timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  actualizado_en timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- RELACIONES PARA LA TABLA detalle_salidas_inventario:
--

--
-- Volcado de datos para la tabla detalle_salidas_inventario
--

INSERT INTO detalle_salidas_inventario (id, salida_inventario_id, producto_id, cantidad, costo_unitario_salida, subtotal, lote, fecha_vencimiento, activo, eliminado, creado_en, actualizado_en) VALUES
(1, 1, 5, 1.00, 9500.00, 9500.00, NULL, NULL, 1, 0, '2025-10-25 04:27:41', '2025-10-25 04:27:41');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla detalle_ventas
--
-- Creación: 05-11-2025 a las 02:15:01
--

DROP TABLE IF EXISTS detalle_ventas;
CREATE TABLE detalle_ventas (
  id int UNSIGNED NOT NULL,
  venta_id int NOT NULL,
  producto_id int NOT NULL,
  horario_ruta_id int DEFAULT NULL COMMENT 'FK para enlazar a una salida de bus específica si es venta de tiquete',
  descripcion_producto text COLLATE utf8mb4_unicode_ci,
  unidad_medida_id int DEFAULT NULL,
  cantidad decimal(10,2) NOT NULL,
  precio_unitario decimal(10,2) NOT NULL,
  descuento_porcentaje decimal(5,2) DEFAULT '0.00',
  monto_descuento_input decimal(10,2) DEFAULT '0.00' COMMENT 'Monto de descuento directo del input',
  subtotal_bruto decimal(12,2) NOT NULL DEFAULT '0.00',
  monto_descuento_calculado decimal(12,2) NOT NULL DEFAULT '0.00' COMMENT 'Monto de descuento final aplicado',
  subtotal_neto decimal(12,2) NOT NULL DEFAULT '0.00',
  codigo_impuesto_dgt varchar(2) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Código DGT del Impuesto (ej: 01 para IVA)',
  tarifa_impuesto_porc decimal(5,2) DEFAULT '0.00' COMMENT 'Tasa de impuesto aplicada a esta línea (ej: 13.00)',
  monto_impuesto decimal(12,2) DEFAULT '0.00' COMMENT 'Monto de impuesto calculado para la línea',
  codigo_tarifa_dgt varchar(2) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Código Tarifa DGT (ej: 08 para 13%)',
  exoneracion_tipo_doc varchar(2) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Tipo de exoneración (si aplica)',
  monto_exoneracion decimal(12,2) DEFAULT '0.00' COMMENT 'Monto exonerado (si aplica)',
  cantidad_entregada decimal(10,2) DEFAULT '0.00',
  activo tinyint(1) DEFAULT '1',
  eliminado tinyint(1) DEFAULT '0',
  creado_en timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  actualizado_en timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Detalle de líneas de producto/servicio vendidas en una factura.';

--
-- RELACIONES PARA LA TABLA detalle_ventas:
--

--
-- Volcado de datos para la tabla detalle_ventas
--

INSERT INTO detalle_ventas (id, venta_id, producto_id, horario_ruta_id, descripcion_producto, unidad_medida_id, cantidad, precio_unitario, descuento_porcentaje, monto_descuento_input, subtotal_bruto, monto_descuento_calculado, subtotal_neto, codigo_impuesto_dgt, tarifa_impuesto_porc, monto_impuesto, codigo_tarifa_dgt, exoneracion_tipo_doc, monto_exoneracion, cantidad_entregada, activo, eliminado, creado_en, actualizado_en) VALUES
(1, 1, 1, NULL, NULL, NULL, 1.00, 600000.00, 0.00, 0.00, 0.00, 0.00, 600000.00, '01', 13.00, 78000.00, '08', NULL, 0.00, 0.00, 1, 0, '2025-10-25 04:27:41', '2025-10-25 04:27:41'),
(2, 2, 4, NULL, 'Venta de Tiquetes (ejemplo)', NULL, 3.00, 3500.00, 0.00, 0.00, 0.00, 0.00, 10500.00, '01', 13.00, 1365.00, '08', NULL, 0.00, 0.00, 1, 0, '2025-10-25 04:27:41', '2025-10-25 04:27:41');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla empleados
--
-- Creación: 05-11-2025 a las 02:15:01
--

DROP TABLE IF EXISTS empleados;
CREATE TABLE empleados (
  id int NOT NULL,
  empresa_id int UNSIGNED NOT NULL,
  nombre varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  primer_apellido varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  segundo_apellido varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  tipo_documento varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  numero_documento varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  fecha_nacimiento date DEFAULT NULL,
  genero varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  fecha_contratacion date NOT NULL,
  cargo_id int UNSIGNED DEFAULT NULL,
  salario decimal(10,2) NOT NULL,
  usuario_id int UNSIGNED DEFAULT NULL,
  direccion text COLLATE utf8mb4_unicode_ci,
  telefono varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  email varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  activo tinyint(1) DEFAULT '1',
  eliminado tinyint(1) DEFAULT '0',
  creado_en timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  actualizado_en timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Información detallada del personal de cada empresa, incluyendo FK a la cuenta de usuario.';

--
-- RELACIONES PARA LA TABLA empleados:
--   cargo_id
--       cargos -> id
--   usuario_id
--       usuarios -> id
--

--
-- Volcado de datos para la tabla empleados
--

INSERT INTO empleados (id, empresa_id, nombre, primer_apellido, segundo_apellido, tipo_documento, numero_documento, fecha_nacimiento, genero, fecha_contratacion, cargo_id, salario, usuario_id, direccion, telefono, email, activo, eliminado, creado_en, actualizado_en) VALUES
(1, 1, 'Empleado A', 'Apellido A', 'García', 'DNI', '12345678A', '1990-05-15', 'Masculino', '2023-01-01', NULL, 2000000.00, 1, 'Dirección Empleado A', '555-1111', 'empleadoA@ejemplo.com', 1, 0, '2025-05-19 06:55:45', '2025-11-01 21:46:55'),
(2, 1, 'Empleado B', 'Apellido B', 'Fernández', 'PASAPORTE', 'XYZ98765', '1995-10-20', 'Femenino', '2023-03-01', NULL, 1500000.00, 2, 'Dirección Empleado B', '555-2222', 'empleadoB@ejemplo.com', 1, 0, '2025-05-19 06:55:45', '2025-11-01 21:46:51'),
(3, 2, 'Empleado C', 'Apellido C', 'López', 'DNI', '87654321B', '1988-08-10', 'Masculino', '2022-09-01', NULL, 1800000.00, 3, 'Dirección Empleado C', '555-3333', 'empleadoC@ejemplo.com', 1, 0, '2025-05-19 06:55:45', '2025-11-01 21:46:48'),
(4, 2, 'Empleado D', 'Apellido D', 'Pérez', 'Cedula_Extranjera', 'CE987654', '2000-02-28', NULL, '2024-01-15', NULL, 1200000.00, NULL, 'Dirección Empleado D', '555-4444', 'empleadoD@ejemplo.com', 1, 0, '2025-05-19 06:55:45', '2025-08-21 04:12:27'),
(5, 3, 'Empleado E', 'Apellido E', 'Pérez', 'Cedula_Nacional', 'CN543210', '1992-12-05', NULL, '2023-05-01', NULL, 1600000.00, NULL, 'Dirección Empleado E', '555-5555', 'empleadoE@ejemplo.com', 1, 0, '2025-05-19 06:55:45', '2025-08-21 04:12:27'),
(6, 1, 'Laura', 'Campos', 'Pérez', 'DNI', '87654321L', '1992-03-10', 'Femenino', '2024-02-01', 1, 3500000.00, 4, 'Dirección Laura Campos', '555-6666', 'laura.campos@ejemplo.com', 1, 0, '2025-05-24 05:52:01', '2025-08-21 04:12:27'),
(7, 2, 'Roberto', 'Jiménez', 'Pérez', 'Pasaporte', 'PAS123456', '1985-07-22', 'Masculino', '2023-11-15', 6, 2800000.00, NULL, 'Dirección Roberto Jiménez', '555-7777', 'roberto.jimenez@ejemplo.com', 1, 0, '2025-05-24 05:52:01', '2025-11-05 00:58:44');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla empresas
--
-- Creación: 05-11-2025 a las 02:15:01
--

DROP TABLE IF EXISTS empresas;
CREATE TABLE empresas (
  id int UNSIGNED NOT NULL,
  nombre varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  nombre_comercial varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  razon_social varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  num_identificacion_dgt varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Número de identificación del Emisor (Cédula, NITE, etc.)',
  tipo_identificacion varchar(2) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  actividad_economica_principal varchar(6) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Código de 6 dígitos de la actividad económica principal (Hacienda)',
  direccion text COLLATE utf8mb4_unicode_ci,
  provincia varchar(2) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Código de Provincia (DGT)',
  canton varchar(2) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Código de Cantón (DGT)',
  distrito varchar(2) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Código de Distrito (DGT)',
  telefono varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  email varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  certificado_llave_fe longtext COLLATE utf8mb4_unicode_ci COMMENT 'Llave criptográfica .p12/pfx (ENCRIPTADA)',
  pin_llave_fe_hash varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'PIN de la llave FE (HASHEADO)',
  prefijo_orden_compra varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  moneda_defecto varchar(3) COLLATE utf8mb4_unicode_ci DEFAULT 'CRC',
  regimen_tributario_id int UNSIGNED DEFAULT NULL,
  activo tinyint(1) DEFAULT '1',
  eliminado tinyint(1) DEFAULT '0',
  creado_en timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  actualizado_en timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Tabla principal que centraliza los datos de cada cliente/tenant del ERP. Base de la arquitectura Multi-Tenant.';

--
-- RELACIONES PARA LA TABLA empresas:
--

--
-- Volcado de datos para la tabla empresas
--

INSERT INTO empresas (id, nombre, nombre_comercial, razon_social, num_identificacion_dgt, tipo_identificacion, actividad_economica_principal, direccion, provincia, canton, distrito, telefono, email, certificado_llave_fe, pin_llave_fe_hash, prefijo_orden_compra, moneda_defecto, regimen_tributario_id, activo, eliminado, creado_en, actualizado_en) VALUES
(1, 'Consultoría URSOL', 'URS01 - Tech & Data', 'URSOL Proyectos S.A.', '3101123456', '02', '620101', NULL, '01', '01', '01', NULL, 'info@consultoriaursol.com', NULL, NULL, NULL, 'CRC', NULL, 1, 0, '2025-10-25 04:27:41', '2025-10-25 04:27:41'),
(2, 'Transportes El Rápido', 'Terminal El Rápido', 'El Rápido Ticos SRL', '3102654321', '02', '492101', NULL, '02', '03', '04', NULL, 'contacto@rapidoticos.com', NULL, NULL, NULL, 'CRC', NULL, 1, 0, '2025-10-25 04:27:41', '2025-10-25 04:27:41'),
(3, 'Dead Moon Tattoos', 'DeadMoonStudio', 'Arte y Tinta CR S.A.', '3101987654', '02', '960200', NULL, '03', '05', '06', NULL, 'citas@deadmoon.com', NULL, NULL, NULL, 'USD', NULL, 1, 0, '2025-10-25 04:27:41', '2025-10-25 04:27:41');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla entidad_etiquetas
--
-- Creación: 04-11-2025 a las 21:39:10
--

DROP TABLE IF EXISTS entidad_etiquetas;
CREATE TABLE entidad_etiquetas (
  id int NOT NULL,
  etiqueta_id int NOT NULL,
  entidad_tipo varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Nombre de la tabla: clientes, productos, ventas, empleados, etc.',
  entidad_id int NOT NULL COMMENT 'ID del registro en la tabla mencionada en entidad_tipo',
  activo tinyint(1) DEFAULT '1',
  eliminado tinyint(1) DEFAULT '0',
  creado_en timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  actualizado_en timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Relación polimórfica entre entidades y etiquetas';

--
-- RELACIONES PARA LA TABLA entidad_etiquetas:
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla entradas_inventario
--
-- Creación: 05-11-2025 a las 02:15:01
--

DROP TABLE IF EXISTS entradas_inventario;
CREATE TABLE entradas_inventario (
  id int NOT NULL,
  empresa_id int UNSIGNED NOT NULL,
  almacen_id int UNSIGNED DEFAULT NULL,
  fecha_entrada datetime NOT NULL,
  tipo_entrada varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  orden_compra_id int DEFAULT NULL,
  proveedor_id int DEFAULT NULL,
  documento_referencia varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  observaciones text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  estado varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'Pendiente',
  monto_total decimal(15,2) DEFAULT '0.00',
  activo tinyint(1) DEFAULT '1',
  eliminado tinyint(1) DEFAULT '0',
  creado_en timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  actualizado_en timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Registra el aumento de stock (compras, ajustes positivos). Documento clave de trazabilidad.';

--
-- RELACIONES PARA LA TABLA entradas_inventario:
--   almacen_id
--       almacenes -> id
--

--
-- Volcado de datos para la tabla entradas_inventario
--

INSERT INTO entradas_inventario (id, empresa_id, almacen_id, fecha_entrada, tipo_entrada, orden_compra_id, proveedor_id, documento_referencia, observaciones, estado, monto_total, activo, eliminado, creado_en, actualizado_en) VALUES
(1, 1, 1, '2025-09-01 10:00:00', 'Compra', NULL, NULL, NULL, NULL, 'Pendiente', 375000.00, 1, 0, '2025-10-25 04:27:41', '2025-10-25 04:27:41');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla etiquetas
--
-- Creación: 04-11-2025 a las 21:40:49
--

DROP TABLE IF EXISTS etiquetas;
CREATE TABLE etiquetas (
  id int NOT NULL,
  empresa_id int UNSIGNED NOT NULL,
  nombre varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Ej: Tatuaje Geométrico, Asesoría Legal, Cliente VIP',
  color_hex varchar(7) COLLATE utf8mb4_unicode_ci DEFAULT '#CCCCCC',
  activo tinyint(1) DEFAULT '1',
  eliminado tinyint(1) DEFAULT '0',
  creado_en timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  actualizado_en timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Etiquetas genéricas para clasificar cualquier entidad';

--
-- RELACIONES PARA LA TABLA etiquetas:
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla formas_pago
--
-- Creación: 21-08-2025 a las 04:34:42
--

DROP TABLE IF EXISTS formas_pago;
CREATE TABLE formas_pago (
  id int NOT NULL,
  nombre varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  descripcion text COLLATE utf8mb4_unicode_ci,
  tipo varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  requiere_referencia tinyint(1) DEFAULT '0',
  activo tinyint(1) DEFAULT NULL,
  eliminado tinyint(1) DEFAULT '0',
  creado_en timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  actualizado_en timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- RELACIONES PARA LA TABLA formas_pago:
--

--
-- Volcado de datos para la tabla formas_pago
--

INSERT INTO formas_pago (id, nombre, descripcion, tipo, requiere_referencia, activo, eliminado, creado_en, actualizado_en) VALUES
(1, 'Efectivo', 'Pago realizado en moneda física o billetes.', '01', 0, NULL, 0, '2025-10-25 04:27:41', '2025-10-25 04:27:41'),
(2, 'Tarjeta', 'Tarjeta de crédito o débito.', '02', 0, NULL, 0, '2025-10-25 04:27:41', '2025-10-25 04:27:41'),
(3, 'Transferencia Electrónica', 'Transferencia bancaria o depósito.', '04', 0, NULL, 0, '2025-10-25 04:27:41', '2025-10-25 04:27:41'),
(4, 'SINPE Móvil', 'Pago mediante el sistema SINPE Móvil.', '06', 0, NULL, 0, '2025-10-25 04:27:41', '2025-10-25 04:27:41');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla horarios_ruta
--
-- Creación: 05-11-2025 a las 02:15:01
--

DROP TABLE IF EXISTS horarios_ruta;
CREATE TABLE horarios_ruta (
  id int NOT NULL,
  ruta_id int NOT NULL,
  bus_id int DEFAULT NULL,
  fecha_salida date NOT NULL COMMENT '1=Domingo, 7=Sábado.',
  hora_salida time NOT NULL,
  fecha_llegada_estimada date DEFAULT NULL,
  hora_llegada_estimada time DEFAULT NULL,
  asientos_disponibles int DEFAULT NULL COMMENT 'Calculado: capacidad del bus - tiquetes vendidos',
  estado varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Programado, Cancelado, En Viaje, Finalizado',
  activo tinyint(1) DEFAULT '1',
  eliminado tinyint(1) DEFAULT '0',
  creado_en timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  actualizado_en timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Programación de un viaje específico: qué bus, en qué ruta y a qué hora.';

--
-- RELACIONES PARA LA TABLA horarios_ruta:
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla marcas
--
-- Creación: 24-05-2025 a las 06:25:15
--

DROP TABLE IF EXISTS marcas;
CREATE TABLE marcas (
  id int NOT NULL,
  nombre varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  descripcion text COLLATE utf8mb4_unicode_ci,
  activo tinyint(1) DEFAULT '1',
  eliminado tinyint(1) DEFAULT '0',
  creado_en timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  actualizado_en timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- RELACIONES PARA LA TABLA marcas:
--

--
-- Volcado de datos para la tabla marcas
--

INSERT INTO marcas (id, nombre, descripcion, activo, eliminado, creado_en, actualizado_en) VALUES
(1, 'Sony', 'Electrónicos de consumo y entretenimiento', 1, 0, '2025-05-22 07:52:20', '2025-05-22 07:52:20'),
(2, 'Samsung', 'Tecnología y electrodomésticos', 1, 0, '2025-05-22 07:52:20', '2025-05-22 07:52:20'),
(3, 'LG', 'Electrodomésticos y electrónica', 1, 0, '2025-05-22 07:52:20', '2025-05-22 07:52:20'),
(4, 'HP', 'Equipos de cómputo e impresoras', 1, 0, '2025-05-22 07:52:20', '2025-05-22 07:52:20'),
(5, 'Dell', 'Computadoras y soluciones tecnológicas', 1, 0, '2025-05-22 07:52:20', '2025-05-22 07:52:20'),
(6, 'Apple', 'Electrónica de consumo y software', 1, 0, '2025-05-22 07:52:20', '2025-05-22 07:52:20'),
(7, 'Genérica', 'Productos sin marca específica o marca blanca', 1, 0, '2025-05-22 07:52:20', '2025-05-22 07:52:20'),
(8, 'Logitech', 'Periféricos de computadora', 1, 0, '2025-05-22 07:52:20', '2025-05-22 07:52:20');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla modelos_buses
--
-- Creación: 05-11-2025 a las 02:15:01
-- Última actualización: 05-11-2025 a las 02:02:47
--

DROP TABLE IF EXISTS modelos_buses;
CREATE TABLE modelos_buses (
  id int UNSIGNED NOT NULL,
  nombre varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Ej: Paradiso 1800 DD, Viaggio 1050'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Tabla normalizada de tipos/modelos de autobuses (ej: Marcopolo, Sprinter). Optimización de texto.';

--
-- RELACIONES PARA LA TABLA modelos_buses:
--

--
-- Volcado de datos para la tabla modelos_buses
--

INSERT INTO modelos_buses (id, nombre) VALUES
(1, 'Paradiso 1800 DD'),
(2, 'Viaggio 1050');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla ordenes_compra
--
-- Creación: 04-11-2025 a las 21:43:04
--

DROP TABLE IF EXISTS ordenes_compra;
CREATE TABLE ordenes_compra (
  id int NOT NULL,
  empresa_id int UNSIGNED NOT NULL,
  numero_orden varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  proveedor_id int NOT NULL,
  almacen_destino_id int DEFAULT NULL,
  moneda varchar(3) COLLATE utf8mb4_unicode_ci DEFAULT 'USD',
  monto_subtotal decimal(15,2) DEFAULT '0.00',
  monto_impuestos decimal(15,2) DEFAULT '0.00',
  fecha_emision date NOT NULL,
  fecha_entrega_estimada date DEFAULT NULL,
  estado varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  monto_total decimal(15,2) DEFAULT '0.00',
  observaciones text COLLATE utf8mb4_unicode_ci,
  activo tinyint(1) DEFAULT '1',
  eliminado tinyint(1) DEFAULT '0',
  creado_en timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  actualizado_en timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- RELACIONES PARA LA TABLA ordenes_compra:
--

--
-- Volcado de datos para la tabla ordenes_compra
--

INSERT INTO ordenes_compra (id, empresa_id, numero_orden, proveedor_id, almacen_destino_id, moneda, monto_subtotal, monto_impuestos, fecha_emision, fecha_entrega_estimada, estado, monto_total, observaciones, activo, eliminado, creado_en, actualizado_en) VALUES
(1, 1, 'OC-AUTO-00001', 1, 1, 'USD', 0.00, 0.00, '2024-07-28', NULL, 'Enviada', 1000.00, 'Orden de compra inicial de suministros de oficina', 1, 0, '2025-05-19 06:48:41', '2025-05-24 06:22:21'),
(2, 1, 'OC-AUTO-00002', 2, 1, 'USD', 0.00, 0.00, '2024-07-29', NULL, 'Recibida', 5000.00, 'Orden de compra de equipos electrónicos', 1, 0, '2025-05-19 06:48:41', '2025-05-24 06:22:21'),
(3, 2, 'OC-AUTO-00003', 3, 3, 'USD', 0.00, 0.00, '2024-07-30', NULL, 'Pendiente', 200.00, 'Orden de compra de materia prima', 1, 0, '2025-05-19 06:48:41', '2025-05-24 06:22:21'),
(4, 2, NULL, 4, NULL, 'USD', 0.00, 0.00, '2024-07-31', NULL, 'Cancelada', 100.00, 'Orden de compra de artículos de limpieza', 1, 0, '2025-05-19 06:48:41', '2025-05-19 06:48:41'),
(5, 3, NULL, 5, NULL, 'USD', 0.00, 0.00, '2024-08-01', NULL, 'Enviada', 1500.00, 'Orden de compra de libros para la biblioteca', 1, 0, '2025-05-19 06:48:41', '2025-05-19 06:48:41'),
(6, 1, 'OC-EMP1-00001', 1, 1, 'USD', 6150.00, 1107.00, '2024-07-20', '2024-08-05', 'Aprobada', 7257.00, 'Pedido de Laptops y Mouses', 1, 0, '2025-05-24 05:52:17', '2025-05-24 05:52:17');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla pagos
--
-- Creación: 04-11-2025 a las 21:44:49
--

DROP TABLE IF EXISTS pagos;
CREATE TABLE pagos (
  id int NOT NULL,
  empresa_id int UNSIGNED NOT NULL,
  orden_compra_id int DEFAULT NULL,
  cuenta_por_pagar_id bigint UNSIGNED DEFAULT NULL,
  proveedor_id int DEFAULT NULL,
  cliente_id int DEFAULT NULL,
  cuenta_por_cobrar_id bigint UNSIGNED DEFAULT NULL,
  forma_pago_id int NOT NULL,
  fecha_pago datetime NOT NULL,
  monto decimal(10,2) NOT NULL,
  moneda varchar(3) COLLATE utf8mb4_unicode_ci DEFAULT 'USD',
  descripcion text COLLATE utf8mb4_unicode_ci,
  referencia varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  estado varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  activo tinyint(1) DEFAULT '1',
  eliminado tinyint(1) DEFAULT '0',
  creado_en timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  actualizado_en timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- RELACIONES PARA LA TABLA pagos:
--

--
-- Volcado de datos para la tabla pagos
--

INSERT INTO pagos (id, empresa_id, orden_compra_id, cuenta_por_pagar_id, proveedor_id, cliente_id, cuenta_por_cobrar_id, forma_pago_id, fecha_pago, monto, moneda, descripcion, referencia, estado, activo, eliminado, creado_en, actualizado_en) VALUES
(1, 1, NULL, NULL, 1, NULL, NULL, 1, '2024-07-28 11:00:00', 1000.00, 'USD', 'Pago a proveedor Alfa por OC-001', 'Cheque #12345', 'Pagado', 1, 0, '2025-05-19 06:49:43', '2025-05-19 06:49:43'),
(2, 1, NULL, NULL, NULL, 2, NULL, 2, '2024-07-29 13:00:00', 800.00, 'USD', 'Pago de cliente B por Venta-002', 'Transacción #56789', 'Pagado', 1, 0, '2025-05-19 06:49:43', '2025-05-19 06:49:43'),
(3, 2, NULL, NULL, 3, NULL, NULL, 3, '2024-07-30 15:00:00', 200.00, 'USD', 'Pago a proveedor Gamma por OC-003', 'Transferencia #98765', 'Pendiente', 1, 0, '2025-05-19 06:49:43', '2025-05-19 06:49:43'),
(4, 2, NULL, NULL, NULL, 4, NULL, 4, '2024-07-31 17:00:00', 300.00, 'USD', 'Pago de cliente D por Venta-004', 'Cheque #23456', 'Cancelado', 1, 0, '2025-05-19 06:49:43', '2025-05-19 06:49:43'),
(5, 3, NULL, NULL, 5, NULL, NULL, 5, '2024-08-01 10:00:00', 1500.00, 'USD', 'Pago a proveedor Epsilon por OC-005', 'Pago Móvil #67890', 'Pagado', 1, 0, '2025-05-19 06:49:43', '2025-05-19 06:49:43'),
(6, 1, NULL, 1, 1, NULL, NULL, 3, '2025-05-27 15:00:17', 50000.00, 'CRC', 'Abono a factura 506010124...', NULL, 'Pagado', 1, 0, '2025-05-27 21:00:17', '2025-05-27 21:00:17');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla pagos_nomina
--
-- Creación: 04-11-2025 a las 21:45:55
--

DROP TABLE IF EXISTS pagos_nomina;
CREATE TABLE pagos_nomina (
  id int NOT NULL,
  empresa_id int UNSIGNED NOT NULL,
  empleado_id int NOT NULL,
  periodo_nomina_id int DEFAULT NULL,
  fecha_pago datetime NOT NULL,
  monto_bruto decimal(10,2) NOT NULL,
  total_deducciones decimal(10,2) NOT NULL,
  monto_neto_pagado decimal(10,2) NOT NULL,
  metodo_pago_id int DEFAULT NULL,
  referencia_pago varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  estado varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'pagado',
  observaciones text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  activo tinyint(1) DEFAULT '1',
  eliminado tinyint(1) DEFAULT '0',
  creado_en timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  actualizado_en timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- RELACIONES PARA LA TABLA pagos_nomina:
--

--
-- Volcado de datos para la tabla pagos_nomina
--

INSERT INTO pagos_nomina (id, empresa_id, empleado_id, periodo_nomina_id, fecha_pago, monto_bruto, total_deducciones, monto_neto_pagado, metodo_pago_id, referencia_pago, estado, observaciones, activo, eliminado, creado_en, actualizado_en) VALUES
(1, 1, 1, 2, '2024-07-31 00:00:00', 2500000.00, 500000.00, 2000000.00, 3, NULL, 'pagado', 'Pago de nómina de julio - Gerente de Ventas', 1, 0, '2025-05-19 06:56:28', '2025-05-24 06:24:11'),
(2, 1, 2, 2, '2024-07-31 00:00:00', 1800000.00, 300000.00, 1500000.00, 3, NULL, 'pagado', 'Pago de nómina de julio - Vendedor', 1, 0, '2025-05-19 06:56:28', '2025-05-24 06:24:11'),
(3, 2, 3, 2, '2024-07-31 00:00:00', 2200000.00, 400000.00, 1800000.00, 3, NULL, 'pagado', 'Pago de nómina de julio - Contador', 1, 0, '2025-05-19 06:56:28', '2025-05-24 06:24:11'),
(4, 2, 4, 2, '2024-07-31 00:00:00', 1500000.00, 300000.00, 1200000.00, 3, NULL, 'pagado', 'Pago de nómina de julio - Asistente Administrativo', 1, 0, '2025-05-19 06:56:28', '2025-05-24 06:24:11'),
(5, 3, 5, 2, '2024-07-31 00:00:00', 2000000.00, 400000.00, 1600000.00, 3, NULL, 'pagado', 'Pago de nómina de julio - Jefe de Almacén', 1, 0, '2025-05-19 06:56:28', '2025-05-24 06:24:11');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla periodos_nomina
--
-- Creación: 04-11-2025 a las 21:46:29
--

DROP TABLE IF EXISTS periodos_nomina;
CREATE TABLE periodos_nomina (
  id int NOT NULL,
  empresa_id int UNSIGNED NOT NULL,
  nombre_periodo varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  fecha_inicio date NOT NULL,
  fecha_fin date NOT NULL,
  fecha_pago_estimada date DEFAULT NULL,
  estado varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Abierto',
  observaciones text COLLATE utf8mb4_unicode_ci,
  activo tinyint(1) DEFAULT '1',
  eliminado tinyint(1) DEFAULT '0',
  creado_en timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  actualizado_en timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- RELACIONES PARA LA TABLA periodos_nomina:
--

--
-- Volcado de datos para la tabla periodos_nomina
--

INSERT INTO periodos_nomina (id, empresa_id, nombre_periodo, fecha_inicio, fecha_fin, fecha_pago_estimada, estado, observaciones, activo, eliminado, creado_en, actualizado_en) VALUES
(1, 1, 'Primera Quincena Julio 2024 - SuperTech', '2024-07-01', '2024-07-15', '2024-07-15', 'Abierto', NULL, 1, 0, '2025-05-22 07:52:20', '2025-05-22 07:52:20'),
(2, 1, 'Segunda Quincena Julio 2024 - SuperTech', '2024-07-16', '2024-07-31', '2024-07-31', 'Abierto', NULL, 1, 0, '2025-05-22 07:52:20', '2025-05-22 07:52:20'),
(3, 2, 'Julio 2024 - Global Solutions', '2024-07-01', '2024-07-31', '2024-08-05', 'Cerrado', NULL, 1, 0, '2025-05-22 07:52:20', '2025-05-22 07:52:20'),
(4, 1, 'Primera Quincena Agosto 2024 - SuperTech', '2024-08-01', '2024-08-15', '2024-08-15', 'Abierto', NULL, 1, 0, '2025-05-22 07:52:20', '2025-05-22 07:52:20');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla permisos
--
-- Creación: 25-10-2025 a las 03:56:52
--

DROP TABLE IF EXISTS permisos;
CREATE TABLE permisos (
  id int NOT NULL,
  nombre varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  descripcion text COLLATE utf8mb4_unicode_ci,
  modulo varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  codigo_unico varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Código interno para uso de la API (Ej: CLI_READ)',
  activo tinyint(1) DEFAULT '1',
  eliminado tinyint(1) DEFAULT '0',
  creado_en timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  actualizado_en timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- RELACIONES PARA LA TABLA permisos:
--

--
-- Volcado de datos para la tabla permisos
--

INSERT INTO permisos (id, nombre, descripcion, modulo, codigo_unico, activo, eliminado, creado_en, actualizado_en) VALUES
(101, 'Ver Módulo Ventas', 'Acceso general al módulo de ventas', NULL, 'VTA-001', 1, 0, '2025-10-25 04:27:41', '2025-10-25 04:27:41'),
(102, 'Crear Facturas', 'Permite emitir facturas', NULL, 'VTA-002', 1, 0, '2025-10-25 04:27:41', '2025-10-25 04:27:41'),
(201, 'Ver Inventario', 'Acceso al inventario físico y Kardex', NULL, 'INV-001', 1, 0, '2025-10-25 04:27:41', '2025-10-25 04:27:41'),
(202, 'Crear Productos', 'Permite ingresar nuevos productos al catálogo', NULL, 'INV-002', 1, 0, '2025-10-25 04:27:41', '2025-10-25 04:27:41');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla presupuestos
--
-- Creación: 04-11-2025 a las 21:46:56
--

DROP TABLE IF EXISTS presupuestos;
CREATE TABLE presupuestos (
  id int NOT NULL,
  empresa_id int UNSIGNED NOT NULL,
  nombre varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  periodo_inicio date NOT NULL,
  periodo_fin date NOT NULL,
  estado varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Borrador, Activo, Finalizado',
  activo tinyint(1) DEFAULT '1',
  eliminado tinyint(1) DEFAULT '0',
  creado_en timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  actualizado_en timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Maestro de Presupuestos financieros';

--
-- RELACIONES PARA LA TABLA presupuestos:
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla productos
--
-- Creación: 05-11-2025 a las 02:15:01
--

DROP TABLE IF EXISTS productos;
CREATE TABLE productos (
  id int NOT NULL,
  empresa_id int UNSIGNED NOT NULL,
  categoria_id int UNSIGNED DEFAULT NULL,
  unidad_medida_id int DEFAULT NULL,
  marca_id int DEFAULT NULL,
  proveedor_id_predeterminado int DEFAULT NULL,
  nombre varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  descripcion text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  codigo_barras varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  sku varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  precio_venta decimal(10,2) NOT NULL,
  precio_compra decimal(12,2) NOT NULL DEFAULT '0.00',
  stock decimal(10,2) NOT NULL DEFAULT '0.00',
  stock_minimo decimal(10,2) NOT NULL DEFAULT '0.00',
  stock_maximo decimal(10,2) NOT NULL DEFAULT '0.00',
  peso decimal(10,2) DEFAULT '0.00' COMMENT 'Peso del producto',
  volumen decimal(10,2) DEFAULT '0.00' COMMENT 'Volumen del producto',
  impuesto_id int UNSIGNED DEFAULT NULL COMMENT 'ID del tipo de impuesto aplicable',
  cabys_id int UNSIGNED DEFAULT NULL,
  tipo_producto varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Almacenable' COMMENT 'Tipo de producto (Almacenable, Servicio, Kit)',
  codigo_tipo_item_dgt varchar(2) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Código DGT: 01 (Bien), 02 (Servicio), 03 (Activo), 04 (Mercadería)',
  activo tinyint(1) DEFAULT '1',
  eliminado tinyint(1) DEFAULT '0',
  creado_en timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  actualizado_en timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Catálogo de productos/servicios. Incluye datos Multi-Tenant y FK al catálogo CAByS.';

--
-- RELACIONES PARA LA TABLA productos:
--   cabys_id
--       cabys -> id
--   categoria_id
--       categorias_productos -> id
--   empresa_id
--       empresas -> id
--   impuesto_id
--       tipos_impuesto -> id
--   unidad_medida_id
--       unidades_medida -> id
--

--
-- Volcado de datos para la tabla productos
--

INSERT INTO productos (id, empresa_id, categoria_id, unidad_medida_id, marca_id, proveedor_id_predeterminado, nombre, descripcion, codigo_barras, sku, precio_venta, precio_compra, stock, stock_minimo, stock_maximo, peso, volumen, impuesto_id, cabys_id, tipo_producto, codigo_tipo_item_dgt, activo, eliminado, creado_en, actualizado_en) VALUES
(1, 1, NULL, NULL, NULL, NULL, 'Paquete Desarrollo API (30 Horas)', NULL, NULL, NULL, 600000.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 1, 1, 'Servicio', NULL, 1, 0, '2025-10-25 04:27:41', '2025-10-25 04:27:41'),
(2, 1, NULL, NULL, NULL, NULL, 'Mantenimiento Mensual (Software)', NULL, NULL, NULL, 150000.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 1, 1, 'Servicio', NULL, 1, 0, '2025-10-25 04:27:41', '2025-10-25 04:27:41'),
(3, 3, NULL, NULL, NULL, NULL, 'Sesión Tatuaje 5 Horas (Diseño Personalizado)', NULL, NULL, NULL, 250.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 1, 2, 'Servicio', NULL, 1, 0, '2025-10-25 04:27:41', '2025-10-25 04:27:41'),
(4, 1, NULL, NULL, NULL, NULL, 'Suministro de Oficina (Caja)', NULL, NULL, NULL, 10000.00, 7500.00, 0.00, 0.00, 0.00, 0.00, 0.00, 1, 4, 'Almacenable', NULL, 1, 0, '2025-10-25 04:27:41', '2025-10-25 04:27:41'),
(5, 2, NULL, NULL, NULL, NULL, 'Filtro de Aceite para Bus (Und)', NULL, NULL, NULL, 15000.00, 9500.00, 0.00, 0.00, 0.00, 0.00, 0.00, 1, 4, 'Almacenable', NULL, 1, 0, '2025-10-25 04:27:41', '2025-10-25 04:27:41'),
(7, 1, NULL, 8, NULL, NULL, 'Consultoría IT Avanzada (8 Horas)', NULL, 'SRV-007', NULL, 450000.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, NULL, 1, 'Servicio', '02', 1, 0, '2025-10-26 02:19:45', '2025-10-26 02:19:45'),
(8, 3, NULL, 8, NULL, NULL, 'Sesión de Tatuaje - 3 Horas', NULL, 'SRV-008', NULL, 80000.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, NULL, 2, 'Servicio', '02', 1, 0, '2025-10-26 02:19:45', '2025-10-26 02:19:45'),
(9, 2, NULL, 1, NULL, NULL, 'Kit Repuesto Eje Bus #1', NULL, 'P-009', NULL, 150000.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, NULL, 4, 'Almacenable', '01', 1, 0, '2025-10-26 02:19:45', '2025-10-26 02:19:45'),
(10, 1, NULL, 8, NULL, NULL, 'Consultoría IT Avanzada (8 Horas)', NULL, 'SRV-010', NULL, 450000.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, NULL, 1, 'Servicio', '02', 1, 0, '2025-10-26 02:29:54', '2025-10-26 02:29:54'),
(11, 3, NULL, 8, NULL, NULL, 'Sesión de Tatuaje - 3 Horas', NULL, 'SRV-011', NULL, 80000.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, NULL, 2, 'Servicio', '02', 1, 0, '2025-10-26 02:29:54', '2025-10-26 02:29:54'),
(12, 2, NULL, 1, NULL, NULL, 'Kit Repuesto Eje Bus #1', NULL, 'P-012', NULL, 150000.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, NULL, 4, 'Almacenable', '01', 1, 0, '2025-10-26 02:29:54', '2025-10-26 02:29:54'),
(13, 1, NULL, 8, NULL, NULL, 'Consultoría IT Avanzada (8 Horas)', NULL, 'SRV-013', NULL, 450000.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, NULL, 1, 'Servicio', '02', 1, 0, '2025-10-26 02:34:49', '2025-10-26 02:34:49'),
(14, 3, NULL, 8, NULL, NULL, 'Sesión de Tatuaje - 3 Horas', NULL, 'SRV-014', NULL, 80000.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, NULL, 2, 'Servicio', '02', 1, 0, '2025-10-26 02:34:49', '2025-10-26 02:34:49'),
(15, 2, NULL, 1, NULL, NULL, 'Kit Repuesto Eje Bus #1', NULL, 'P-015', NULL, 150000.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, NULL, 4, 'Almacenable', '01', 1, 0, '2025-10-26 02:34:49', '2025-10-26 02:34:49'),
(50, 1, NULL, 8, NULL, NULL, 'Consultoría IT Avanzada (8 Horas)', NULL, 'SRV-050', NULL, 450000.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, NULL, 1, 'Servicio', '02', 1, 0, '2025-10-26 02:31:28', '2025-10-26 02:31:28'),
(51, 3, NULL, 8, NULL, NULL, 'Sesión de Tatuaje - 3 Horas', NULL, 'SRV-051', NULL, 80000.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, NULL, 2, 'Servicio', '02', 1, 0, '2025-10-26 02:31:29', '2025-10-26 02:31:29'),
(52, 2, NULL, 1, NULL, NULL, 'Kit Repuesto Eje Bus #1', NULL, 'P-052', NULL, 150000.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, NULL, 4, 'Almacenable', '01', 1, 0, '2025-10-26 02:31:29', '2025-10-26 02:31:29');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla proveedores
--
-- Creación: 05-11-2025 a las 00:27:37
--

DROP TABLE IF EXISTS proveedores;
CREATE TABLE proveedores (
  id int UNSIGNED NOT NULL,
  empresa_id int UNSIGNED NOT NULL,
  nombre varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  razon_social varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  nit_ruc varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  direccion text COLLATE utf8mb4_unicode_ci,
  telefono varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  email varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  dias_credito int DEFAULT '30' COMMENT 'Días de crédito otorgados por el proveedor',
  activo tinyint(1) DEFAULT '1',
  eliminado tinyint(1) DEFAULT '0',
  creado_en timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  actualizado_en timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- RELACIONES PARA LA TABLA proveedores:
--

--
-- Volcado de datos para la tabla proveedores
--

INSERT INTO proveedores (id, empresa_id, nombre, razon_social, nit_ruc, direccion, telefono, email, dias_credito, activo, eliminado, creado_en, actualizado_en) VALUES
(1, 1, 'Suministros Costa Rica', NULL, '301010101', NULL, NULL, NULL, 30, 1, 0, '2025-10-25 04:27:41', '2025-10-25 04:27:41'),
(2, 2, 'Repuestos para Buses S.A.', NULL, '302020202', NULL, NULL, NULL, 30, 1, 0, '2025-10-25 04:27:41', '2025-10-25 04:27:41');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla regimenes_tributarios
--
-- Creación: 27-05-2025 a las 20:24:00
--

DROP TABLE IF EXISTS regimenes_tributarios;
CREATE TABLE regimenes_tributarios (
  id int NOT NULL,
  codigo varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  nombre varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  descripcion text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  activo tinyint(1) NOT NULL DEFAULT '1',
  eliminado tinyint(1) NOT NULL DEFAULT '0',
  creado_en timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  actualizado_en timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Catálogo de Regímenes Tributarios de Costa Rica';

--
-- RELACIONES PARA LA TABLA regimenes_tributarios:
--

--
-- Volcado de datos para la tabla regimenes_tributarios
--

INSERT INTO regimenes_tributarios (id, codigo, nombre, descripcion, activo, eliminado, creado_en, actualizado_en) VALUES
(1, '01', 'Régimen Tradicional', 'Régimen General del Impuesto sobre la Renta y el IVA.', 1, 0, '2025-05-27 20:24:33', '2025-05-27 20:24:33'),
(2, '02', 'Régimen Simplificado', 'Régimen de Tributación Simplificada.', 1, 0, '2025-05-27 20:24:33', '2025-05-27 20:24:33'),
(3, '05', 'Régimen Especial Agropecuario', 'Régimen para el sector agropecuario.', 1, 0, '2025-05-27 20:24:33', '2025-05-27 20:24:33');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla roles
--
-- Creación: 05-11-2025 a las 02:15:01
--

DROP TABLE IF EXISTS roles;
CREATE TABLE roles (
  id int NOT NULL,
  nombre varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  descripcion text COLLATE utf8mb4_unicode_ci,
  activo tinyint(1) DEFAULT '1',
  eliminado tinyint(1) DEFAULT '0',
  creado_en timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  actualizado_en timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Define los niveles de permiso (ej: Administrador, Contador, Vendedor) para los usuarios.';

--
-- RELACIONES PARA LA TABLA roles:
--

--
-- Volcado de datos para la tabla roles
--

INSERT INTO roles (id, nombre, descripcion, activo, eliminado, creado_en, actualizado_en) VALUES
(1, 'Administrador Global', NULL, 1, 0, '2025-10-25 04:27:41', '2025-10-25 04:27:41'),
(2, 'Contador', NULL, 1, 0, '2025-10-25 04:27:41', '2025-10-25 04:27:41'),
(3, 'Jefe de Flota', NULL, 1, 0, '2025-10-25 04:27:41', '2025-10-25 04:27:41'),
(4, 'Artista Tatuador', NULL, 1, 0, '2025-10-25 04:27:41', '2025-10-25 04:27:41');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla roles_permisos
--
-- Creación: 04-11-2025 a las 21:58:36
--

DROP TABLE IF EXISTS roles_permisos;
CREATE TABLE roles_permisos (
  id int NOT NULL,
  permiso_id int NOT NULL,
  activo tinyint(1) DEFAULT '1',
  creado_en timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  actualizado_en timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- RELACIONES PARA LA TABLA roles_permisos:
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla rutas
--
-- Creación: 05-11-2025 a las 02:15:01
--

DROP TABLE IF EXISTS rutas;
CREATE TABLE rutas (
  id int NOT NULL,
  empresa_id int UNSIGNED NOT NULL,
  nombre varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Ej: San José - Limón',
  origen varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  destino varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  distancia_km decimal(10,2) DEFAULT NULL,
  duracion_estimada int DEFAULT NULL COMMENT 'En minutos',
  tarifa_base decimal(10,2) NOT NULL COMMENT 'Precio base del tiquete',
  observaciones text COLLATE utf8mb4_unicode_ci,
  activo tinyint(1) DEFAULT '1',
  eliminado tinyint(1) DEFAULT '0',
  creado_en timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  actualizado_en timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Define el trayecto o ruta de transporte (origen, destino, descripción).';

--
-- RELACIONES PARA LA TABLA rutas:
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla salidas_inventario
--
-- Creación: 05-11-2025 a las 02:15:01
--

DROP TABLE IF EXISTS salidas_inventario;
CREATE TABLE salidas_inventario (
  id int NOT NULL,
  empresa_id int UNSIGNED NOT NULL,
  almacen_id int UNSIGNED DEFAULT NULL,
  fecha_salida datetime NOT NULL,
  tipo_salida varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Venta, Ajuste Negativo, Devolución Proveedor, Transferencia, Consumo Interno',
  venta_id int DEFAULT NULL,
  cliente_id int UNSIGNED DEFAULT NULL,
  proveedor_id int DEFAULT NULL,
  documento_referencia varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  estado varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Pendiente' COMMENT 'Pendiente, Procesada, Cancelada',
  monto_total decimal(15,2) NOT NULL DEFAULT '0.00' COMMENT 'Costo total de los productos salientes',
  observaciones text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  descripcion text COLLATE utf8mb4_unicode_ci,
  activo tinyint(1) NOT NULL DEFAULT '1',
  eliminado tinyint(1) DEFAULT '0',
  creado_en timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  actualizado_en timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Registra la disminución de stock (ventas, mermas, ajustes negativos). Documento clave de trazabilidad.';

--
-- RELACIONES PARA LA TABLA salidas_inventario:
--   almacen_id
--       almacenes -> id
--   cliente_id
--       clientes -> id
--

--
-- Volcado de datos para la tabla salidas_inventario
--

INSERT INTO salidas_inventario (id, empresa_id, almacen_id, fecha_salida, tipo_salida, venta_id, cliente_id, proveedor_id, documento_referencia, estado, monto_total, observaciones, descripcion, activo, eliminado, creado_en, actualizado_en) VALUES
(1, 2, 2, '2025-10-25 08:00:00', 'Venta', NULL, NULL, NULL, NULL, 'Cancelada', 9500.00, 'Consumo interno de repuesto para unidad de transporte.', NULL, 1, 0, '2025-10-25 04:27:41', '2025-10-29 03:21:11'),
(2, 3, 3, '2025-10-25 08:00:00', 'Consumo Interno', NULL, NULL, NULL, NULL, 'Cancelada', 15000.00, 'Consumo interno de insumos de tatuaje, tintas', NULL, 1, 0, '2025-10-25 04:27:41', '2025-10-29 03:22:05');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla sucursales
--
-- Creación: 05-11-2025 a las 02:15:01
--

DROP TABLE IF EXISTS sucursales;
CREATE TABLE sucursales (
  id int UNSIGNED NOT NULL,
  empresa_id int UNSIGNED NOT NULL,
  nombre varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  direccion text COLLATE utf8mb4_unicode_ci,
  telefono varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  email varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  activo tinyint(1) DEFAULT '1',
  eliminado tinyint(1) DEFAULT '0',
  creado_en timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  actualizado_en timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Estructura organizacional de cada empresa (matriz, sucursales, oficinas). Enlaza a empresas.';

--
-- RELACIONES PARA LA TABLA sucursales:
--   empresa_id
--       empresas -> id
--

--
-- Volcado de datos para la tabla sucursales
--

INSERT INTO sucursales (id, empresa_id, nombre, direccion, telefono, email, activo, eliminado, creado_en, actualizado_en) VALUES
(1, 1, 'Oficina Central (Tech)', 'San José, Montes de Oca', '64567788', 'techno88493@gmi2.com', 1, 0, '2025-10-25 04:27:41', '2025-10-29 03:16:58'),
(2, 2, 'Terminal Principal', 'Alajuela, San Ramón, Centro', '33227799', 'Terminalita38209@ewlo.com', 1, 0, '2025-10-25 04:27:41', '2025-10-29 03:16:48'),
(3, 3, 'Estudio de Tatuajes', 'Heredia, Zona Comercial', '45655676', 'porktetatuatis999@ufale.com', 1, 0, '2025-10-25 04:27:41', '2025-10-29 03:17:23');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla tasas_impuesto
--
-- Creación: 04-11-2025 a las 22:06:56
--

DROP TABLE IF EXISTS tasas_impuesto;
CREATE TABLE tasas_impuesto (
  id int UNSIGNED NOT NULL,
  tipo_impuesto_id int UNSIGNED NOT NULL,
  tasa_porcentaje decimal(5,2) NOT NULL COMMENT 'Tasa del impuesto en porcentaje, ej: 13.00 para 13%',
  fecha_inicio_vigencia date NOT NULL COMMENT 'Fecha desde la cual esta tasa es válida',
  fecha_fin_vigencia date DEFAULT NULL COMMENT 'Fecha hasta la cual esta tasa es válida (NULL si es indefinida)',
  descripcion varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Descripción adicional, ej: Ley N° XXXX',
  activo tinyint(1) NOT NULL DEFAULT '1',
  eliminado tinyint(1) NOT NULL DEFAULT '0',
  creado_en timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  actualizado_en timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Tasas de impuesto y su vigencia';

--
-- RELACIONES PARA LA TABLA tasas_impuesto:
--

--
-- Volcado de datos para la tabla tasas_impuesto
--

INSERT INTO tasas_impuesto (id, tipo_impuesto_id, tasa_porcentaje, fecha_inicio_vigencia, fecha_fin_vigencia, descripcion, activo, eliminado, creado_en, actualizado_en) VALUES
(1, 1, 13.00, '2025-10-24', NULL, 'IVA General 13% (Tarifa 08)', 1, 0, '2025-10-25 04:27:41', '2025-10-25 04:27:41'),
(2, 1, 0.00, '2025-10-24', NULL, 'IVA Exento 0% (Tarifa 01)', 1, 0, '2025-10-25 04:27:41', '2025-10-25 04:27:41');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla tipos_cambio_historial
--
-- Creación: 27-05-2025 a las 20:26:25
--

DROP TABLE IF EXISTS tipos_cambio_historial;
CREATE TABLE tipos_cambio_historial (
  id int NOT NULL,
  fecha date NOT NULL,
  moneda_origen char(3) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'USD',
  moneda_destino char(3) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'CRC',
  tasa_compra decimal(12,5) NOT NULL,
  tasa_venta decimal(12,5) NOT NULL,
  fuente varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'BCCR',
  creado_en timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Historial de tipos de cambio oficiales';

--
-- RELACIONES PARA LA TABLA tipos_cambio_historial:
--

--
-- Volcado de datos para la tabla tipos_cambio_historial
--

INSERT INTO tipos_cambio_historial (id, fecha, moneda_origen, moneda_destino, tasa_compra, tasa_venta, fuente, creado_en) VALUES
(1, '2025-05-26', 'USD', 'CRC', 500.50000, 505.75000, 'BCCR', '2025-05-27 20:26:39'),
(2, '2025-05-27', 'USD', 'CRC', 501.00000, 506.25000, 'BCCR', '2025-05-27 20:26:39');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla tipos_cuentas
--
-- Creación: 05-11-2025 a las 01:28:03
--

DROP TABLE IF EXISTS tipos_cuentas;
CREATE TABLE tipos_cuentas (
  id int UNSIGNED NOT NULL,
  nombre varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  descripcion text COLLATE utf8mb4_unicode_ci,
  naturaleza enum('Deudora','Acreedora') COLLATE utf8mb4_unicode_ci NOT NULL,
  activo tinyint(1) DEFAULT '1',
  eliminado tinyint(1) DEFAULT '0',
  creado_en timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  actualizado_en timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- RELACIONES PARA LA TABLA tipos_cuentas:
--

--
-- Volcado de datos para la tabla tipos_cuentas
--

INSERT INTO tipos_cuentas (id, nombre, descripcion, naturaleza, activo, eliminado, creado_en, actualizado_en) VALUES
(1, 'Activo Corriente', 'Bienes y derechos líquidos en menos de un año', 'Deudora', 1, 0, '2025-05-22 07:52:20', '2025-05-22 07:52:20'),
(2, 'Activo No Corriente', 'Bienes y derechos con permanencia mayor a un año', 'Deudora', 1, 0, '2025-05-22 07:52:20', '2025-05-22 07:52:20'),
(3, 'Pasivo Corriente', 'Deudas y obligaciones a corto plazo (menos de un año)', 'Acreedora', 1, 0, '2025-05-22 07:52:20', '2025-05-22 07:52:20'),
(4, 'Pasivo No Corriente', 'Deudas y obligaciones a largo plazo (más de un año)', 'Acreedora', 1, 0, '2025-05-22 07:52:20', '2025-05-22 07:52:20'),
(5, 'Patrimonio Neto', 'Recursos propios de la empresa', 'Acreedora', 1, 0, '2025-05-22 07:52:20', '2025-05-22 07:52:20'),
(6, 'Ingresos de Operación', 'Ingresos por la actividad principal de la empresa', 'Acreedora', 1, 0, '2025-05-22 07:52:20', '2025-05-22 07:52:20'),
(7, 'Costos de Venta', 'Costo de los bienes vendidos o servicios prestados', 'Deudora', 1, 0, '2025-05-22 07:52:20', '2025-05-22 07:52:20'),
(8, 'Gastos de Operación', 'Gastos necesarios para la actividad de la empresa', 'Deudora', 1, 0, '2025-05-22 07:52:20', '2025-05-22 07:52:20');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla tipos_impuesto
--
-- Creación: 29-10-2025 a las 03:10:31
--

DROP TABLE IF EXISTS tipos_impuesto;
CREATE TABLE tipos_impuesto (
  id int UNSIGNED NOT NULL,
  codigo_hacienda varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  nombre varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Nombre descriptivo del impuesto',
  descripcion text COLLATE utf8mb4_unicode_ci,
  Comentario text COLLATE utf8mb4_unicode_ci NOT NULL,
  activo tinyint(1) NOT NULL DEFAULT '1',
  eliminado tinyint(1) NOT NULL DEFAULT '0',
  creado_en timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  actualizado_en timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Catálogo de tipos de impuesto';

--
-- RELACIONES PARA LA TABLA tipos_impuesto:
--

--
-- Volcado de datos para la tabla tipos_impuesto
--

INSERT INTO tipos_impuesto (id, codigo_hacienda, nombre, descripcion, Comentario, activo, eliminado, creado_en, actualizado_en) VALUES
(1, '01', 'IVA', 'Codigo IVA de Hacienda C.R.', 'NO BORRAR, Gracias', 1, 0, '2025-10-25 04:27:41', '2025-10-29 03:11:20');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla tiquetes_detalle
--
-- Creación: 05-11-2025 a las 02:15:01
--

DROP TABLE IF EXISTS tiquetes_detalle;
CREATE TABLE tiquetes_detalle (
  id int NOT NULL,
  detalle_venta_id int UNSIGNED NOT NULL,
  horario_ruta_id int NOT NULL COMMENT 'FK al horario de ruta (viaje) específico',
  asiento_numero varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Número de asiento (Ej: 1A, 25)',
  nombre_pasajero varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  identificacion_pasajero varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  precio_final_tiquete decimal(10,2) NOT NULL,
  estado varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Vendido, Usado, Cancelado',
  activo tinyint(1) DEFAULT '1',
  eliminado tinyint(1) DEFAULT '0',
  creado_en timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  actualizado_en timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Registro del tiquete vendido. Detalle del asiento y pasajero asociado a un horario de ruta.';

--
-- RELACIONES PARA LA TABLA tiquetes_detalle:
--   detalle_venta_id
--       detalle_ventas -> id
--   horario_ruta_id
--       horarios_ruta -> id
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla unidades_medida
--
-- Creación: 05-11-2025 a las 02:15:01
--

DROP TABLE IF EXISTS unidades_medida;
CREATE TABLE unidades_medida (
  id int NOT NULL,
  nombre varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  abreviatura varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  codigo_oficial varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT 'E99',
  activo tinyint(1) DEFAULT '1',
  eliminado tinyint(1) DEFAULT '0',
  creado_en timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  actualizado_en timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Clasificadores estandarizados para inventario (ej: Unidad, Kilogramo, Metro).';

--
-- RELACIONES PARA LA TABLA unidades_medida:
--

--
-- Volcado de datos para la tabla unidades_medida
--

INSERT INTO unidades_medida (id, nombre, abreviatura, codigo_oficial, activo, eliminado, creado_en, actualizado_en) VALUES
(1, 'Unidad', 'Und', 'E99', 1, 0, '2025-05-22 07:52:20', '2025-05-22 07:52:20'),
(2, 'Kilogramo', 'Kg', 'E99', 1, 0, '2025-05-22 07:52:20', '2025-05-22 07:52:20'),
(3, 'Litro', 'Lt', 'E99', 1, 0, '2025-05-22 07:52:20', '2025-05-22 07:52:20'),
(4, 'Metro', 'm', 'E99', 1, 0, '2025-05-22 07:52:20', '2025-05-22 07:52:20'),
(5, 'Caja', 'Cja', 'E99', 1, 0, '2025-05-22 07:52:20', '2025-05-22 07:52:20'),
(6, 'Paquete', 'Paq', 'E99', 1, 0, '2025-05-22 07:52:20', '2025-05-22 07:52:20'),
(7, 'Servicio', 'Serv', 'E99', 1, 0, '2025-05-22 07:52:20', '2025-05-22 07:52:20'),
(8, 'Hora', 'Hr', 'E99', 1, 0, '2025-05-22 07:52:20', '2025-05-22 07:52:20'),
(9, 'Mililitro', 'ml', 'E99', 1, 0, '2025-10-26 02:44:53', '2025-10-26 02:44:53'),
(10, 'Gramo', 'g', 'E99', 1, 0, '2025-10-26 02:44:53', '2025-10-26 02:44:53'),
(11, 'Miligramo', 'mg', 'E99', 1, 0, '2025-10-26 02:44:53', '2025-10-26 02:44:53'),
(12, 'Centímetro', 'cm', 'E99', 1, 0, '2025-10-26 02:44:53', '2025-10-26 02:44:53'),
(13, 'Milímetro', 'mm', 'E99', 1, 0, '2025-10-26 02:44:53', '2025-10-26 02:44:53'),
(14, 'Kilómetro', 'Km', 'E99', 1, 0, '2025-10-26 02:44:53', '2025-10-26 02:44:53'),
(15, 'Pie', 'ft', 'E99', 1, 0, '2025-10-26 02:44:53', '2025-10-26 02:44:53'),
(16, 'Pulgada', 'in', 'E99', 1, 0, '2025-10-26 02:44:53', '2025-10-26 02:44:53'),
(17, 'Yarda', 'yd', 'E99', 1, 0, '2025-10-26 02:44:53', '2025-10-26 02:44:53'),
(18, 'Milla', 'mi', 'E99', 1, 0, '2025-10-26 02:44:53', '2025-10-26 02:44:53'),
(19, 'Metro Cuadrado', 'm²', 'E99', 1, 0, '2025-10-26 02:44:53', '2025-10-26 02:44:53'),
(20, 'Metro Cúbico', 'm³', 'E99', 1, 0, '2025-10-26 02:44:53', '2025-10-26 02:44:53'),
(21, 'Hectárea', 'ha', 'E99', 1, 0, '2025-10-26 02:44:53', '2025-10-26 02:44:53'),
(22, 'Galón', 'Gal', 'E99', 1, 0, '2025-10-26 02:44:53', '2025-10-26 02:44:53'),
(23, 'Barril', 'Bbl', 'E99', 1, 0, '2025-10-26 02:44:53', '2025-10-26 02:44:53'),
(24, 'Tonelada Métrica', 't', 'E99', 1, 0, '2025-10-26 02:44:53', '2025-10-26 02:44:53'),
(25, 'Libra', 'lb', 'E99', 1, 0, '2025-10-26 02:44:53', '2025-10-26 02:44:53'),
(26, 'Onza', 'oz', 'E99', 1, 0, '2025-10-26 02:44:53', '2025-10-26 02:44:53'),
(27, 'Docena', 'Doz', 'E99', 1, 0, '2025-10-26 02:44:53', '2025-10-26 02:44:53'),
(28, 'Gruesa', 'Grs', 'E99', 1, 0, '2025-10-26 02:44:53', '2025-10-26 02:44:53'),
(29, 'Par', 'Par', 'E99', 1, 0, '2025-10-26 02:44:53', '2025-10-26 02:44:53'),
(30, 'Juegos', 'Jgo', 'E99', 1, 0, '2025-10-26 02:44:53', '2025-10-26 02:44:53'),
(31, 'Kit', 'Kit', 'E99', 1, 0, '2025-10-26 02:44:53', '2025-10-26 02:44:53'),
(32, 'Lote', 'Lote', 'E99', 1, 0, '2025-10-26 02:44:53', '2025-10-26 02:44:53'),
(33, 'Rollo', 'Rol', 'E99', 1, 0, '2025-10-26 02:44:53', '2025-10-26 02:44:53'),
(34, 'Bulto', 'Bto', 'E99', 1, 0, '2025-10-26 02:44:53', '2025-10-26 02:44:53'),
(35, 'Frasco', 'Fco', 'E99', 1, 0, '2025-10-26 02:44:53', '2025-10-26 02:44:53'),
(36, 'Lata', 'Lata', 'E99', 1, 0, '2025-10-26 02:44:53', '2025-10-26 02:44:53'),
(37, 'Ampolla', 'Amp', 'E99', 1, 0, '2025-10-26 02:44:53', '2025-10-26 02:44:53'),
(38, 'Blíster', 'Bli', 'E99', 1, 0, '2025-10-26 02:44:53', '2025-10-26 02:44:53'),
(39, 'Cápsula', 'Cap', 'E99', 1, 0, '2025-10-26 02:44:53', '2025-10-26 02:44:53'),
(40, 'Tableta', 'Tab', 'E99', 1, 0, '2025-10-26 02:44:53', '2025-10-26 02:44:53'),
(41, 'Minuto', 'Min', 'E99', 1, 0, '2025-10-26 02:44:53', '2025-10-26 02:44:53'),
(42, 'Segundo', 'Seg', 'E99', 1, 0, '2025-10-26 02:44:53', '2025-10-26 02:44:53'),
(43, 'Día', 'Día', 'E99', 1, 0, '2025-10-26 02:44:53', '2025-10-26 02:44:53'),
(44, 'Semana', 'Sem', 'E99', 1, 0, '2025-10-26 02:44:53', '2025-10-26 02:44:53'),
(45, 'Mes', 'Mes', 'E99', 1, 0, '2025-10-26 02:44:53', '2025-10-26 02:44:53'),
(46, 'Año', 'Año', 'E99', 1, 0, '2025-10-26 02:44:53', '2025-10-26 02:44:53'),
(47, 'Viaje', 'Vje', 'E99', 1, 0, '2025-10-26 02:44:53', '2025-10-26 02:44:53'),
(48, 'Recorrido', 'Rec', 'E99', 1, 0, '2025-10-26 02:44:53', '2025-10-26 02:44:53'),
(49, 'Ticket', 'Tkt', 'E99', 1, 0, '2025-10-26 02:44:53', '2025-10-26 02:44:53'),
(50, 'Contenedor', 'Cont', 'E99', 1, 0, '2025-10-26 02:44:53', '2025-10-26 02:44:53'),
(51, 'Estación de Trabajo', 'EstTrab', 'E99', 1, 0, '2025-10-26 02:44:53', '2025-10-26 02:44:53'),
(52, 'Licencia', 'Lic', 'E99', 1, 0, '2025-10-26 02:44:53', '2025-10-26 02:44:53'),
(53, 'Instalación', 'Inst', 'E99', 1, 0, '2025-10-26 02:44:53', '2025-10-26 02:44:53'),
(54, 'Consulta', 'Cons', 'E99', 1, 0, '2025-10-26 02:44:53', '2025-10-26 02:44:53'),
(55, 'Diseño', 'Dsg', 'E99', 1, 0, '2025-10-26 02:44:53', '2025-10-26 02:44:53'),
(56, 'Pieza', 'Pza', 'E99', 1, 0, '2025-10-26 02:44:53', '2025-10-26 02:44:53'),
(57, 'Centímetro Cúbico', 'cm³', 'E99', 1, 0, '2025-10-26 02:44:53', '2025-10-26 02:44:53'),
(58, 'Milímetro Cúbico', 'mm³', 'E99', 1, 0, '2025-10-26 02:44:53', '2025-10-26 02:44:53'),
(59, 'Candela', 'cd', 'E99', 1, 0, '2025-10-26 02:44:53', '2025-10-26 02:44:53'),
(60, 'Voltio', 'V', 'E99', 1, 0, '2025-10-26 02:44:53', '2025-10-26 02:44:53');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla usuarios
--
-- Creación: 05-11-2025 a las 02:15:01
--

DROP TABLE IF EXISTS usuarios;
CREATE TABLE usuarios (
  id int UNSIGNED NOT NULL,
  nombre varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  apellidos varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  cargo_id int UNSIGNED DEFAULT NULL,
  email varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  password_hash varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  empresa_id int UNSIGNED DEFAULT NULL,
  telefono varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  direccion text COLLATE utf8mb4_unicode_ci,
  activo tinyint(1) DEFAULT '1',
  eliminado tinyint(1) DEFAULT '0',
  creado_en timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  actualizado_en timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Cuentas de acceso al sistema, autenticación y gestión de roles.';

--
-- RELACIONES PARA LA TABLA usuarios:
--

--
-- Volcado de datos para la tabla usuarios
--

INSERT INTO usuarios (id, nombre, apellidos, cargo_id, email, password_hash, empresa_id, telefono, direccion, activo, eliminado, creado_en, actualizado_en) VALUES
(1, 'Eduardo', 'Ureña', NULL, 'admin@ursol.com', 'hashed_pass_1', 1, '77889920', 'palmares, alajuela', 1, 0, '2025-10-25 04:27:41', '2025-11-01 21:42:50'),
(2, 'Contador', 'Ficticio', NULL, 'contador@ursol.com', 'hashed_pass_2', 1, '66554433', 'la sabana, san josé', 1, 0, '2025-10-25 04:27:41', '2025-10-26 02:42:11'),
(3, 'Javier', 'Flota', NULL, 'flota@rapido.com', 'hashed_pass_3', 2, '22331144', 'carrillo, guanacaste', 1, 0, '2025-10-25 04:27:41', '2025-10-26 02:42:25'),
(4, 'Artista', 'Ficticio', NULL, 'tatuador@deadmoon.com', 'hashed_pass_4', 3, '44660022', 'puerto limón, limón', 1, 0, '2025-10-25 04:27:41', '2025-10-26 02:42:37');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla usuarios_roles
--
-- Creación: 04-11-2025 a las 22:11:15
--

DROP TABLE IF EXISTS usuarios_roles;
CREATE TABLE usuarios_roles (
  usuario_id int UNSIGNED NOT NULL,
  rol_id int NOT NULL,
  activo tinyint(1) DEFAULT '1',
  creado_en timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  actualizado_en timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- RELACIONES PARA LA TABLA usuarios_roles:
--

--
-- Volcado de datos para la tabla usuarios_roles
--

INSERT INTO usuarios_roles (usuario_id, rol_id, activo, creado_en, actualizado_en) VALUES
(1, 1, 1, '2025-10-25 04:27:41', '2025-10-25 04:27:41'),
(2, 2, 1, '2025-10-25 04:27:41', '2025-10-25 04:27:41'),
(3, 3, 1, '2025-10-25 04:27:41', '2025-10-25 04:27:41'),
(4, 4, 1, '2025-10-25 04:27:41', '2025-10-25 04:27:41');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla ventas
--
-- Creación: 05-11-2025 a las 02:15:01
--

DROP TABLE IF EXISTS ventas;
CREATE TABLE ventas (
  id int UNSIGNED NOT NULL,
  empresa_id int UNSIGNED NOT NULL,
  sucursal_id int NOT NULL,
  cliente_id int DEFAULT NULL,
  usuario_id int UNSIGNED NOT NULL,
  fecha_venta datetime NOT NULL,
  fecha_vencimiento date DEFAULT NULL,
  tipo_comprobante varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  serie_comprobante varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  numero_comprobante varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  clave_numerica_hacienda varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Clave única de 50 dígitos enviada a Hacienda',
  consecutivo_hacienda varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Consecutivo fiscal completo (ej: 00100001010000000001)',
  moneda varchar(3) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'USD',
  subtotal_bruto_total decimal(12,2) DEFAULT '0.00',
  monto_descuento_total decimal(12,2) DEFAULT '0.00',
  subtotal_neto_total decimal(12,2) DEFAULT '0.00',
  monto_impuesto_total decimal(12,2) DEFAULT '0.00',
  monto_total_venta decimal(10,2) NOT NULL,
  estado_venta varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  condicion_pago varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  condicion_venta_dgt varchar(2) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Código Condición Venta DGT: 01 (Contado), 02 (Crédito), 06 (SINPE Móvil)',
  plazo_credito_dias int DEFAULT '0' COMMENT 'Plazo de crédito en días',
  observaciones text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  xml_enviado longtext COLLATE utf8mb4_unicode_ci COMMENT 'Copia del XML firmado enviado a la DGT',
  xml_respuesta_hacienda longtext COLLATE utf8mb4_unicode_ci COMMENT 'Respuesta de aceptación/rechazo del MH',
  estado_hacienda varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'Pendiente' COMMENT 'Estado DGT: Aceptado, Rechazado, Procesando',
  tipo_referencia_doc varchar(2) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Código Tipo Documento de Referencia (DGT: 01, 02, etc.)',
  clave_referencia_doc varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Clave Numérica de la Factura/Tiquete que se modifica',
  forma_pago_id int NOT NULL,
  activo tinyint(1) DEFAULT '1',
  eliminado tinyint(1) DEFAULT '0',
  creado_en timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  actualizado_en timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  fecha_emision_hacienda datetime DEFAULT NULL COMMENT 'Fecha y hora en que se genera la clave'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Encabezado de la factura/venta electrónica (tiquete, nota, etc.). Central para Hacienda y Contabilidad.';

--
-- RELACIONES PARA LA TABLA ventas:
--

--
-- Volcado de datos para la tabla ventas
--

INSERT INTO ventas (id, empresa_id, sucursal_id, cliente_id, usuario_id, fecha_venta, fecha_vencimiento, tipo_comprobante, serie_comprobante, numero_comprobante, clave_numerica_hacienda, consecutivo_hacienda, moneda, subtotal_bruto_total, monto_descuento_total, subtotal_neto_total, monto_impuesto_total, monto_total_venta, estado_venta, condicion_pago, condicion_venta_dgt, plazo_credito_dias, observaciones, xml_enviado, xml_respuesta_hacienda, estado_hacienda, tipo_referencia_doc, clave_referencia_doc, forma_pago_id, activo, eliminado, creado_en, actualizado_en, fecha_emision_hacienda) VALUES
(1, 1, 1, 1, 1, '2025-10-05 10:00:00', NULL, '01', NULL, NULL, '506051025100000001000000000100000000010000000001', '00100001010000000001', 'USD', 0.00, 0.00, 600000.00, 78000.00, 678000.00, 'Aceptada', NULL, '01', 0, NULL, NULL, NULL, 'Aceptado', NULL, NULL, 3, 1, 0, '2025-10-25 04:27:41', '2025-10-25 04:27:41', NULL),
(2, 2, 2, 3, 3, '2025-10-24 14:30:00', NULL, '04', NULL, NULL, '506051025100000002000000000200000000020000000002', '00100001040000000001', 'USD', 0.00, 0.00, 10500.00, 1365.00, 11865.00, 'Aceptada', NULL, '01', 0, NULL, NULL, NULL, 'Aceptado', NULL, NULL, 2, 1, 0, '2025-10-25 04:27:41', '2025-10-25 04:27:41', NULL);

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla almacenes
--
ALTER TABLE almacenes
  ADD PRIMARY KEY (id),
  ADD KEY fk_almacen_sucursal (sucursal_id),
  ADD KEY fk_almacenes_empresa (empresa_id);

--
-- Indices de la tabla asientos_contables
--
ALTER TABLE asientos_contables
  ADD PRIMARY KEY (id),
  ADD KEY fk_asientos_empresa (empresa_id);

--
-- Indices de la tabla buses_unidades
--
ALTER TABLE buses_unidades
  ADD PRIMARY KEY (id),
  ADD UNIQUE KEY buses_unidades_ibfk_1 (empresa_id,placa),
  ADD KEY fk_bus_modelo (modelo_id);

--
-- Indices de la tabla cabys
--
ALTER TABLE cabys
  ADD PRIMARY KEY (id),
  ADD UNIQUE KEY uq_cabys_codigo (codigo);

--
-- Indices de la tabla cajas
--
ALTER TABLE cajas
  ADD PRIMARY KEY (id),
  ADD KEY cajas_ibfk_1 (sucursal_id);

--
-- Indices de la tabla caja_chica
--
ALTER TABLE caja_chica
  ADD PRIMARY KEY (id),
  ADD KEY caja_chica_ibfk_1 (empresa_id),
  ADD KEY caja_chica_ibfk_2 (responsable_id);

--
-- Indices de la tabla cargos
--
ALTER TABLE cargos
  ADD PRIMARY KEY (id),
  ADD UNIQUE KEY nombre_unico (nombre);

--
-- Indices de la tabla categorias_productos
--
ALTER TABLE categorias_productos
  ADD PRIMARY KEY (id),
  ADD UNIQUE KEY categorias_productos_ibfk_1 (empresa_id,nombre);

--
-- Indices de la tabla clientes
--
ALTER TABLE clientes
  ADD PRIMARY KEY (id),
  ADD UNIQUE KEY clientes_ibfk_1 (empresa_id,tipo_documento_interno,num_identificacion_dgt) USING BTREE;

--
-- Indices de la tabla comprobantes_recibidos_electronicos
--
ALTER TABLE comprobantes_recibidos_electronicos
  ADD PRIMARY KEY (id),
  ADD UNIQUE KEY fk_comprec_empresa (empresa_id,clave_numerica),
  ADD KEY fk_comprec_proveedor (proveedor_id),
  ADD KEY fk_comprec_usuario_conf (usuario_confirmacion_id),
  ADD KEY fk_comprec_entrada_inv (entrada_inventario_id);

--
-- Indices de la tabla configuraciones
--
ALTER TABLE configuraciones
  ADD PRIMARY KEY (id),
  ADD UNIQUE KEY configuraciones_ibfk_1 (empresa_id,clave);

--
-- Indices de la tabla consecutivos_fe
--
ALTER TABLE consecutivos_fe
  ADD PRIMARY KEY (id),
  ADD UNIQUE KEY fk_consecutivo_empresa_tipo_prefijo (empresa_id,tipo_documento_dgt,prefijo),
  ADD KEY consecutivos_fe_ibfk_1 (empresa_id),
  ADD KEY consecutivos_fe_ibfk_2 (sucursal_id);

--
-- Indices de la tabla cuentas_contables
--
ALTER TABLE cuentas_contables
  ADD PRIMARY KEY (id),
  ADD KEY cuentas_contables_ibfk_1 (empresa_id,codigo) USING BTREE,
  ADD KEY fk_ctacont_padre (cuenta_padre_id),
  ADD KEY fk_ctacont_tipocuenta_final (tipo_cuenta_id);

--
-- Indices de la tabla cuentas_por_cobrar
--
ALTER TABLE cuentas_por_cobrar
  ADD PRIMARY KEY (id),
  ADD KEY fk_cxc_empresa (empresa_id),
  ADD KEY fk_cxc_cliente (cliente_id),
  ADD KEY fk_cxc_venta (venta_id);

--
-- Indices de la tabla cuentas_por_pagar
--
ALTER TABLE cuentas_por_pagar
  ADD PRIMARY KEY (id),
  ADD KEY fk_cxp_empresa (empresa_id),
  ADD KEY fk_cxp_proveedor (proveedor_id),
  ADD KEY fk_cxp_orden_compra (orden_compra_id),
  ADD KEY fk_cxp_comprobante_recibido (comprobante_recibido_id);

--
-- Indices de la tabla detalle_asientos
--
ALTER TABLE detalle_asientos
  ADD PRIMARY KEY (id),
  ADD KEY detalle_asientos_ibfk_1 (asiento_contable_id),
  ADD KEY detalle_asientos_ibfk_2 (cuenta_contable_id);

--
-- Indices de la tabla detalle_entradas_inventario
--
ALTER TABLE detalle_entradas_inventario
  ADD PRIMARY KEY (id),
  ADD KEY detalle_entradas_inventario_ibfk_1 (entrada_inventario_id),
  ADD KEY detalle_entradas_inventario_ibfk_2 (producto_id);

--
-- Indices de la tabla detalle_ordenes_compra
--
ALTER TABLE detalle_ordenes_compra
  ADD PRIMARY KEY (id),
  ADD KEY detalle_ordenes_compra_ibfk_1 (orden_compra_id),
  ADD KEY fk_detoc_unidadmed (unidad_medida_id),
  ADD KEY detalle_ordenes_compra_ibfk_2 (producto_id);

--
-- Indices de la tabla detalle_presupuestos
--
ALTER TABLE detalle_presupuestos
  ADD PRIMARY KEY (id),
  ADD KEY detalle_presupuestos_ibfk_1 (presupuesto_id),
  ADD KEY detalle_presupuestos_ibfk_2 (cuenta_contable_id);

--
-- Indices de la tabla detalle_salidas_inventario
--
ALTER TABLE detalle_salidas_inventario
  ADD PRIMARY KEY (id),
  ADD KEY detalle_salidas_inventario_ibfk_1 (salida_inventario_id),
  ADD KEY detalle_salidas_inventario_ibfk_2 (producto_id);

--
-- Indices de la tabla detalle_ventas
--
ALTER TABLE detalle_ventas
  ADD PRIMARY KEY (id),
  ADD KEY detalle_ventas_ibfk_1 (venta_id),
  ADD KEY fk_detventa_unidadmed (unidad_medida_id),
  ADD KEY detalle_ventas_ibfk_2 (producto_id),
  ADD KEY fk_detventa_horario (horario_ruta_id);

--
-- Indices de la tabla empleados
--
ALTER TABLE empleados
  ADD PRIMARY KEY (id),
  ADD UNIQUE KEY empleados_ibfk_1 (empresa_id,tipo_documento,numero_documento) USING BTREE,
  ADD UNIQUE KEY fk_empleado_usuario_final (usuario_id) USING BTREE,
  ADD KEY fk_empleado_cargo_final (cargo_id);

--
-- Indices de la tabla empresas
--
ALTER TABLE empresas
  ADD PRIMARY KEY (id),
  ADD UNIQUE KEY nit_ruc (num_identificacion_dgt),
  ADD UNIQUE KEY fk_email (email),
  ADD KEY fk_empresa_regimen (regimen_tributario_id);

--
-- Indices de la tabla entidad_etiquetas
--
ALTER TABLE entidad_etiquetas
  ADD PRIMARY KEY (id),
  ADD UNIQUE KEY uq_entidad_etiqueta (etiqueta_id,entidad_tipo,entidad_id),
  ADD KEY entidad_etiquetas_ibfk_1 (etiqueta_id);

--
-- Indices de la tabla entradas_inventario
--
ALTER TABLE entradas_inventario
  ADD PRIMARY KEY (id),
  ADD KEY entradas_inventario_ibfk_1 (almacen_id),
  ADD KEY fk_ei_empresa_final (empresa_id),
  ADD KEY fk_ei_oc_final (orden_compra_id),
  ADD KEY fk_ei_proveedor_final (proveedor_id);

--
-- Indices de la tabla etiquetas
--
ALTER TABLE etiquetas
  ADD PRIMARY KEY (id),
  ADD KEY etiquetas_ibfk_1 (empresa_id);

--
-- Indices de la tabla formas_pago
--
ALTER TABLE formas_pago
  ADD PRIMARY KEY (id),
  ADD UNIQUE KEY uq_formapago_nombre (nombre);

--
-- Indices de la tabla horarios_ruta
--
ALTER TABLE horarios_ruta
  ADD PRIMARY KEY (id),
  ADD KEY horarios_ruta_ibfk_1 (ruta_id),
  ADD KEY horarios_ruta_ibfk_2 (bus_id);

--
-- Indices de la tabla marcas
--
ALTER TABLE marcas
  ADD PRIMARY KEY (id),
  ADD UNIQUE KEY nombre_unico (nombre);

--
-- Indices de la tabla modelos_buses
--
ALTER TABLE modelos_buses
  ADD PRIMARY KEY (id),
  ADD UNIQUE KEY nombre (nombre);

--
-- Indices de la tabla ordenes_compra
--
ALTER TABLE ordenes_compra
  ADD PRIMARY KEY (id),
  ADD UNIQUE KEY ordenes_compra_ibfk_1 (empresa_id,numero_orden) USING BTREE,
  ADD KEY ordenes_compra_ibfk_2 (proveedor_id),
  ADD KEY fk_oc_almacen_destino_final (almacen_destino_id);

--
-- Indices de la tabla pagos
--
ALTER TABLE pagos
  ADD PRIMARY KEY (id),
  ADD KEY pagos_ibfk_1 (empresa_id),
  ADD KEY pagos_ibfk_2 (proveedor_id),
  ADD KEY pagos_ibfk_3 (cliente_id),
  ADD KEY pagos_ibfk_4 (forma_pago_id),
  ADD KEY fk_pago_oc (orden_compra_id),
  ADD KEY fk_pago_cxc (cuenta_por_cobrar_id),
  ADD KEY fk_pago_cxp (cuenta_por_pagar_id);

--
-- Indices de la tabla pagos_nomina
--
ALTER TABLE pagos_nomina
  ADD PRIMARY KEY (id),
  ADD KEY pagos_nomina_ibfk_1 (empresa_id),
  ADD KEY pagos_nomina_ibfk_2 (empleado_id),
  ADD KEY fk_pagonom_periodo_final (periodo_nomina_id),
  ADD KEY fk_pagonom_formapago_final (metodo_pago_id);

--
-- Indices de la tabla periodos_nomina
--
ALTER TABLE periodos_nomina
  ADD PRIMARY KEY (id),
  ADD KEY periodos_nomina_ibfk_1 (empresa_id);

--
-- Indices de la tabla permisos
--
ALTER TABLE permisos
  ADD PRIMARY KEY (id),
  ADD UNIQUE KEY nombre (nombre),
  ADD UNIQUE KEY codigo_unico (codigo_unico);

--
-- Indices de la tabla presupuestos
--
ALTER TABLE presupuestos
  ADD PRIMARY KEY (id),
  ADD KEY presupuestos_ibfk_1 (empresa_id);

--
-- Indices de la tabla productos
--
ALTER TABLE productos
  ADD PRIMARY KEY (id),
  ADD UNIQUE KEY productos_fk_empresa_1 (empresa_id,sku),
  ADD UNIQUE KEY productos_fk_empresa_2 (empresa_id,codigo_barras),
  ADD KEY productos_fk_categoria (categoria_id),
  ADD KEY productos_fk_unidad_medida (unidad_medida_id),
  ADD KEY productos_fk_marca (marca_id),
  ADD KEY productos_fk_proveedor (proveedor_id_predeterminado),
  ADD KEY productos_fk_impuesto (impuesto_id),
  ADD KEY fk_producto_cabys (cabys_id);

--
-- Indices de la tabla proveedores
--
ALTER TABLE proveedores
  ADD PRIMARY KEY (id),
  ADD UNIQUE KEY proveedores_ibfk_1 (empresa_id,nit_ruc);

--
-- Indices de la tabla regimenes_tributarios
--
ALTER TABLE regimenes_tributarios
  ADD PRIMARY KEY (id),
  ADD UNIQUE KEY uq_regimen_codigo (codigo),
  ADD UNIQUE KEY uq_regimen_nombre (nombre);

--
-- Indices de la tabla roles
--
ALTER TABLE roles
  ADD PRIMARY KEY (id) USING BTREE,
  ADD UNIQUE KEY roles_ibfk_1 (nombre);

--
-- Indices de la tabla roles_permisos
--
ALTER TABLE roles_permisos
  ADD PRIMARY KEY (id) USING BTREE,
  ADD KEY roles_permisos_ibfk_2 (permiso_id);

--
-- Indices de la tabla rutas
--
ALTER TABLE rutas
  ADD PRIMARY KEY (id),
  ADD KEY rutas_ibfk_1 (empresa_id);

--
-- Indices de la tabla salidas_inventario
--
ALTER TABLE salidas_inventario
  ADD PRIMARY KEY (id),
  ADD KEY fk_salidainv_empresa (empresa_id),
  ADD KEY fk_salidainv_venta (venta_id),
  ADD KEY fk_salidainv_cliente (cliente_id),
  ADD KEY fk_salidainv_proveedor (proveedor_id),
  ADD KEY fk_si_almacen (almacen_id);

--
-- Indices de la tabla sucursales
--
ALTER TABLE sucursales
  ADD PRIMARY KEY (id),
  ADD UNIQUE KEY sucursales_ibfk_1 (empresa_id);

--
-- Indices de la tabla tasas_impuesto
--
ALTER TABLE tasas_impuesto
  ADD PRIMARY KEY (id),
  ADD KEY fk_tasas_impuesto_tipo_impuesto_id (tipo_impuesto_id);

--
-- Indices de la tabla tipos_cambio_historial
--
ALTER TABLE tipos_cambio_historial
  ADD PRIMARY KEY (id),
  ADD UNIQUE KEY uq_tipocambio_fecha_monedas (fecha,moneda_origen,moneda_destino);

--
-- Indices de la tabla tipos_cuentas
--
ALTER TABLE tipos_cuentas
  ADD PRIMARY KEY (id),
  ADD UNIQUE KEY nombre_unico (nombre);

--
-- Indices de la tabla tipos_impuesto
--
ALTER TABLE tipos_impuesto
  ADD PRIMARY KEY (id),
  ADD UNIQUE KEY uq_tipos_impuesto_codigo (codigo_hacienda);

--
-- Indices de la tabla tiquetes_detalle
--
ALTER TABLE tiquetes_detalle
  ADD PRIMARY KEY (id),
  ADD UNIQUE KEY tiquetes_detalle_ibfk_2 (horario_ruta_id,asiento_numero),
  ADD KEY tiquetes_detalle_ibfk_1 (detalle_venta_id);

--
-- Indices de la tabla unidades_medida
--
ALTER TABLE unidades_medida
  ADD PRIMARY KEY (id),
  ADD UNIQUE KEY nombre_unico (nombre),
  ADD UNIQUE KEY abreviatura_unica (abreviatura);

--
-- Indices de la tabla usuarios
--
ALTER TABLE usuarios
  ADD PRIMARY KEY (id),
  ADD UNIQUE KEY email (email),
  ADD KEY fk_usuarios_empresa (empresa_id),
  ADD KEY fk_usuarios_cargo (cargo_id);

--
-- Indices de la tabla usuarios_roles
--
ALTER TABLE usuarios_roles
  ADD PRIMARY KEY (usuario_id,rol_id),
  ADD UNIQUE KEY usuarios_roles_ibfk_1 (usuario_id),
  ADD UNIQUE KEY usuarios_roles_ibfk_2 (rol_id);

--
-- Indices de la tabla ventas
--
ALTER TABLE ventas
  ADD PRIMARY KEY (id),
  ADD UNIQUE KEY ventas_ibfk_1 (empresa_id,tipo_comprobante,serie_comprobante,numero_comprobante),
  ADD UNIQUE KEY clave_numerica_hacienda (clave_numerica_hacienda),
  ADD KEY ventas_ibfk_2 (sucursal_id),
  ADD KEY ventas_ibfk_3 (cliente_id),
  ADD KEY ventas_ibfk_4 (usuario_id),
  ADD KEY ventas_ibfk_5 (forma_pago_id);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla almacenes
--
ALTER TABLE almacenes
  MODIFY id int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla asientos_contables
--
ALTER TABLE asientos_contables
  MODIFY id int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla buses_unidades
--
ALTER TABLE buses_unidades
  MODIFY id int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla cabys
--
ALTER TABLE cabys
  MODIFY id int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla cajas
--
ALTER TABLE cajas
  MODIFY id int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla caja_chica
--
ALTER TABLE caja_chica
  MODIFY id int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla cargos
--
ALTER TABLE cargos
  MODIFY id int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de la tabla categorias_productos
--
ALTER TABLE categorias_productos
  MODIFY id int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla clientes
--
ALTER TABLE clientes
  MODIFY id int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla comprobantes_recibidos_electronicos
--
ALTER TABLE comprobantes_recibidos_electronicos
  MODIFY id bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla configuraciones
--
ALTER TABLE configuraciones
  MODIFY id int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla consecutivos_fe
--
ALTER TABLE consecutivos_fe
  MODIFY id int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla cuentas_contables
--
ALTER TABLE cuentas_contables
  MODIFY id int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=303;

--
-- AUTO_INCREMENT de la tabla cuentas_por_cobrar
--
ALTER TABLE cuentas_por_cobrar
  MODIFY id bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla cuentas_por_pagar
--
ALTER TABLE cuentas_por_pagar
  MODIFY id bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla detalle_asientos
--
ALTER TABLE detalle_asientos
  MODIFY id int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla detalle_entradas_inventario
--
ALTER TABLE detalle_entradas_inventario
  MODIFY id int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla detalle_ordenes_compra
--
ALTER TABLE detalle_ordenes_compra
  MODIFY id int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de la tabla detalle_presupuestos
--
ALTER TABLE detalle_presupuestos
  MODIFY id int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla detalle_salidas_inventario
--
ALTER TABLE detalle_salidas_inventario
  MODIFY id int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla detalle_ventas
--
ALTER TABLE detalle_ventas
  MODIFY id int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla empleados
--
ALTER TABLE empleados
  MODIFY id int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de la tabla empresas
--
ALTER TABLE empresas
  MODIFY id int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla entidad_etiquetas
--
ALTER TABLE entidad_etiquetas
  MODIFY id int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla entradas_inventario
--
ALTER TABLE entradas_inventario
  MODIFY id int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla etiquetas
--
ALTER TABLE etiquetas
  MODIFY id int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla formas_pago
--
ALTER TABLE formas_pago
  MODIFY id int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla horarios_ruta
--
ALTER TABLE horarios_ruta
  MODIFY id int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla marcas
--
ALTER TABLE marcas
  MODIFY id int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de la tabla modelos_buses
--
ALTER TABLE modelos_buses
  MODIFY id int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla ordenes_compra
--
ALTER TABLE ordenes_compra
  MODIFY id int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla pagos
--
ALTER TABLE pagos
  MODIFY id int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla pagos_nomina
--
ALTER TABLE pagos_nomina
  MODIFY id int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla periodos_nomina
--
ALTER TABLE periodos_nomina
  MODIFY id int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla permisos
--
ALTER TABLE permisos
  MODIFY id int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=203;

--
-- AUTO_INCREMENT de la tabla presupuestos
--
ALTER TABLE presupuestos
  MODIFY id int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla productos
--
ALTER TABLE productos
  MODIFY id int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=53;

--
-- AUTO_INCREMENT de la tabla proveedores
--
ALTER TABLE proveedores
  MODIFY id int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla regimenes_tributarios
--
ALTER TABLE regimenes_tributarios
  MODIFY id int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla roles
--
ALTER TABLE roles
  MODIFY id int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla rutas
--
ALTER TABLE rutas
  MODIFY id int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla salidas_inventario
--
ALTER TABLE salidas_inventario
  MODIFY id int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla sucursales
--
ALTER TABLE sucursales
  MODIFY id int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla tasas_impuesto
--
ALTER TABLE tasas_impuesto
  MODIFY id int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla tipos_cambio_historial
--
ALTER TABLE tipos_cambio_historial
  MODIFY id int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla tipos_cuentas
--
ALTER TABLE tipos_cuentas
  MODIFY id int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de la tabla tipos_impuesto
--
ALTER TABLE tipos_impuesto
  MODIFY id int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla tiquetes_detalle
--
ALTER TABLE tiquetes_detalle
  MODIFY id int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla unidades_medida
--
ALTER TABLE unidades_medida
  MODIFY id int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=61;

--
-- AUTO_INCREMENT de la tabla usuarios
--
ALTER TABLE usuarios
  MODIFY id int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla ventas
--
ALTER TABLE ventas
  MODIFY id int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla almacenes
--
ALTER TABLE almacenes
  ADD CONSTRAINT fk_almacen_sucursal FOREIGN KEY (sucursal_id) REFERENCES sucursales (id) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT fk_almacenes_empresa FOREIGN KEY (empresa_id) REFERENCES empresas (id) ON DELETE RESTRICT ON UPDATE CASCADE;

--
-- Filtros para la tabla asientos_contables
--
ALTER TABLE asientos_contables
  ADD CONSTRAINT fk_asientos_empresa FOREIGN KEY (empresa_id) REFERENCES empresas (id) ON DELETE RESTRICT ON UPDATE CASCADE;

--
-- Filtros para la tabla buses_unidades
--
ALTER TABLE buses_unidades
  ADD CONSTRAINT buses_unidades_ibfk_1 FOREIGN KEY (empresa_id) REFERENCES empresas (id),
  ADD CONSTRAINT fk_bus_modelo FOREIGN KEY (modelo_id) REFERENCES modelos_buses (id) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Filtros para la tabla cajas
--
ALTER TABLE cajas
  ADD CONSTRAINT fk_cajas_sucursal FOREIGN KEY (sucursal_id) REFERENCES sucursales (id) ON DELETE RESTRICT ON UPDATE CASCADE;

--
-- Filtros para la tabla caja_chica
--
ALTER TABLE caja_chica
  ADD CONSTRAINT caja_chica_ibfk_1 FOREIGN KEY (empresa_id) REFERENCES empresas (id),
  ADD CONSTRAINT caja_chica_ibfk_2 FOREIGN KEY (responsable_id) REFERENCES usuarios (id);

--
-- Filtros para la tabla comprobantes_recibidos_electronicos
--
ALTER TABLE comprobantes_recibidos_electronicos
  ADD CONSTRAINT fk_comprec_empresa FOREIGN KEY (empresa_id) REFERENCES empresas (id),
  ADD CONSTRAINT fk_comprec_entrada_inv FOREIGN KEY (entrada_inventario_id) REFERENCES entradas_inventario (id) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT fk_comprec_proveedor FOREIGN KEY (proveedor_id) REFERENCES proveedores (id) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT fk_comprec_usuario_conf FOREIGN KEY (usuario_confirmacion_id) REFERENCES usuarios (id) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Filtros para la tabla cuentas_contables
--
ALTER TABLE cuentas_contables
  ADD CONSTRAINT cuentas_contables_ibfk_2 FOREIGN KEY (cuenta_padre_id) REFERENCES cuentas_contables (id),
  ADD CONSTRAINT fk_ctacont_padre FOREIGN KEY (cuenta_padre_id) REFERENCES cuentas_contables (id) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT fk_ctacont_tipocuenta_final FOREIGN KEY (tipo_cuenta_id) REFERENCES tipos_cuentas (id) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT fk_cuentacont_empresa FOREIGN KEY (empresa_id) REFERENCES empresas (id) ON DELETE RESTRICT ON UPDATE CASCADE;

--
-- Filtros para la tabla cuentas_por_cobrar
--
ALTER TABLE cuentas_por_cobrar
  ADD CONSTRAINT fk_cxc_cliente_final FOREIGN KEY (cliente_id) REFERENCES clientes (id) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT fk_cxc_venta_final FOREIGN KEY (venta_id) REFERENCES ventas (id) ON DELETE RESTRICT ON UPDATE CASCADE;

--
-- Filtros para la tabla empleados
--
ALTER TABLE empleados
  ADD CONSTRAINT fk_empleado_cargo FOREIGN KEY (cargo_id) REFERENCES cargos (id) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT fk_empleado_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios (id) ON DELETE RESTRICT ON UPDATE CASCADE;

--
-- Filtros para la tabla entradas_inventario
--
ALTER TABLE entradas_inventario
  ADD CONSTRAINT fk_ei_almacen FOREIGN KEY (almacen_id) REFERENCES almacenes (id) ON DELETE RESTRICT ON UPDATE CASCADE;

--
-- Filtros para la tabla productos
--
ALTER TABLE productos
  ADD CONSTRAINT fk_producto_cabys FOREIGN KEY (cabys_id) REFERENCES cabys (id) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT fk_productos_categoria FOREIGN KEY (categoria_id) REFERENCES categorias_productos (id) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT fk_productos_empresa FOREIGN KEY (empresa_id) REFERENCES empresas (id) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT fk_productos_impuesto FOREIGN KEY (impuesto_id) REFERENCES tipos_impuesto (id) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT fk_productos_unidad_medida FOREIGN KEY (unidad_medida_id) REFERENCES unidades_medida (id) ON DELETE RESTRICT ON UPDATE CASCADE;

--
-- Filtros para la tabla salidas_inventario
--
ALTER TABLE salidas_inventario
  ADD CONSTRAINT fk_si_almacen FOREIGN KEY (almacen_id) REFERENCES almacenes (id) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT fk_si_cliente FOREIGN KEY (cliente_id) REFERENCES clientes (id) ON DELETE RESTRICT ON UPDATE CASCADE;

--
-- Filtros para la tabla sucursales
--
ALTER TABLE sucursales
  ADD CONSTRAINT fk_sucursales_empresa FOREIGN KEY (empresa_id) REFERENCES empresas (id) ON DELETE RESTRICT ON UPDATE CASCADE;

--
-- Filtros para la tabla tiquetes_detalle
--
ALTER TABLE tiquetes_detalle
  ADD CONSTRAINT fk_tiquete_detalleventa FOREIGN KEY (detalle_venta_id) REFERENCES detalle_ventas (id) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT fk_tiquete_horario FOREIGN KEY (horario_ruta_id) REFERENCES horarios_ruta (id) ON DELETE RESTRICT ON UPDATE CASCADE;


--
-- Metadatos
--
USE phpmyadmin;

--
-- Metadatos para la tabla almacenes
--

--
-- Metadatos para la tabla asientos_contables
--

--
-- Metadatos para la tabla buses_unidades
--

--
-- Volcado de datos para la tabla pma__table_uiprefs
--

INSERT INTO pma__table_uiprefs (username, db_name, table_name, prefs, last_update) VALUES
('nuevo_usuario', 'api_db', 'buses_unidades', '[]', '2025-11-03 00:05:27');

--
-- Metadatos para la tabla cabys
--

--
-- Volcado de datos para la tabla pma__table_uiprefs
--

INSERT INTO pma__table_uiprefs (username, db_name, table_name, prefs, last_update) VALUES
('nuevo_usuario', 'api_db', 'cabys', '{\"CREATE_TIME\":\"2025-05-27 14:22:48\"}', '2025-10-29 03:05:18');

--
-- Metadatos para la tabla cajas
--

--
-- Metadatos para la tabla caja_chica
--

--
-- Metadatos para la tabla cargos
--

--
-- Metadatos para la tabla categorias_productos
--

--
-- Metadatos para la tabla clientes
--

--
-- Metadatos para la tabla comprobantes_recibidos_electronicos
--

--
-- Metadatos para la tabla configuraciones
--

--
-- Metadatos para la tabla consecutivos_fe
--

--
-- Metadatos para la tabla cuentas_contables
--

--
-- Volcado de datos para la tabla pma__table_uiprefs
--

INSERT INTO pma__table_uiprefs (username, db_name, table_name, prefs, last_update) VALUES
('nuevo_usuario', 'api_db', 'cuentas_contables', '[]', '2025-05-22 08:34:23');

--
-- Metadatos para la tabla cuentas_por_cobrar
--

--
-- Metadatos para la tabla cuentas_por_pagar
--

--
-- Metadatos para la tabla detalle_asientos
--

--
-- Metadatos para la tabla detalle_entradas_inventario
--

--
-- Volcado de datos para la tabla pma__table_uiprefs
--

INSERT INTO pma__table_uiprefs (username, db_name, table_name, prefs, last_update) VALUES
('nuevo_usuario', 'api_db', 'detalle_entradas_inventario', '{\"CREATE_TIME\":\"2025-05-27 13:55:31\"}', '2025-08-19 04:48:24');

--
-- Metadatos para la tabla detalle_ordenes_compra
--

--
-- Metadatos para la tabla detalle_presupuestos
--

--
-- Metadatos para la tabla detalle_salidas_inventario
--

--
-- Metadatos para la tabla detalle_ventas
--

--
-- Metadatos para la tabla empleados
--

--
-- Volcado de datos para la tabla pma__table_uiprefs
--

INSERT INTO pma__table_uiprefs (username, db_name, table_name, prefs, last_update) VALUES
('nuevo_usuario', 'api_db', 'empleados', '[]', '2025-11-01 21:46:36');

--
-- Metadatos para la tabla empresas
--

--
-- Metadatos para la tabla entidad_etiquetas
--

--
-- Metadatos para la tabla entradas_inventario
--

--
-- Metadatos para la tabla etiquetas
--

--
-- Metadatos para la tabla formas_pago
--

--
-- Metadatos para la tabla horarios_ruta
--

--
-- Metadatos para la tabla marcas
--

--
-- Metadatos para la tabla modelos_buses
--

--
-- Metadatos para la tabla ordenes_compra
--

--
-- Metadatos para la tabla pagos
--

--
-- Metadatos para la tabla pagos_nomina
--

--
-- Metadatos para la tabla periodos_nomina
--

--
-- Metadatos para la tabla permisos
--

--
-- Metadatos para la tabla presupuestos
--

--
-- Metadatos para la tabla productos
--

--
-- Metadatos para la tabla proveedores
--

--
-- Metadatos para la tabla regimenes_tributarios
--

--
-- Metadatos para la tabla roles
--

--
-- Metadatos para la tabla roles_permisos
--

--
-- Metadatos para la tabla rutas
--

--
-- Metadatos para la tabla salidas_inventario
--

--
-- Volcado de datos para la tabla pma__table_uiprefs
--

INSERT INTO pma__table_uiprefs (username, db_name, table_name, prefs, last_update) VALUES
('nuevo_usuario', 'api_db', 'salidas_inventario', '{\"sorted_col\":\"`empresa_id` ASC\"}', '2025-10-29 03:20:25');

--
-- Metadatos para la tabla sucursales
--

--
-- Metadatos para la tabla tasas_impuesto
--

--
-- Metadatos para la tabla tipos_cambio_historial
--

--
-- Metadatos para la tabla tipos_cuentas
--

--
-- Metadatos para la tabla tipos_impuesto
--

--
-- Metadatos para la tabla tiquetes_detalle
--

--
-- Metadatos para la tabla unidades_medida
--

--
-- Metadatos para la tabla usuarios
--

--
-- Volcado de datos para la tabla pma__table_uiprefs
--

INSERT INTO pma__table_uiprefs (username, db_name, table_name, prefs, last_update) VALUES
('nuevo_usuario', 'api_db', 'usuarios', '{\"CREATE_TIME\":\"2025-05-19 02:54:12\"}', '2025-11-01 21:29:10');

--
-- Metadatos para la tabla usuarios_roles
--

--
-- Metadatos para la tabla ventas
--

--
-- Metadatos para la base de datos api_db
--
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
