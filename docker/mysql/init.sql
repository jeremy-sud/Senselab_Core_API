-- Inicialización de Base de Datos para Ursol CAST API
-- Este script se ejecuta automáticamente al crear el contenedor

-- Crear base de datos de testing
CREATE DATABASE IF NOT EXISTS `api_db_testing` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Otorgar permisos al usuario en ambas bases de datos
GRANT ALL PRIVILEGES ON `api_db`.* TO 'ursol_user'@'%';
GRANT ALL PRIVILEGES ON `api_db_testing`.* TO 'ursol_user'@'%';

-- Aplicar cambios
FLUSH PRIVILEGES;

-- Mensaje de confirmación
SELECT 'Bases de datos creadas exitosamente: api_db, api_db_testing' AS message;
