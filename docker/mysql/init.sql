-- Inicialización de Base de Datos para Senselab Core API
-- Este script se ejecuta automáticamente al crear el contenedor

-- Crear base de datos de testing
CREATE DATABASE IF NOT EXISTS `senselab_landlord_testing` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Crear el usuario en caso de que no exista
CREATE USER IF NOT EXISTS 'senselab_admin'@'%' IDENTIFIED BY 'rX8#mK9vL2@pQ5&tW7*';

-- Otorgar permisos al usuario en ambas bases de datos
GRANT ALL PRIVILEGES ON `senselab_landlord`.* TO 'senselab_admin'@'%';
GRANT ALL PRIVILEGES ON `senselab_landlord_testing`.* TO 'senselab_admin'@'%';

-- Aplicar cambios
FLUSH PRIVILEGES;

-- Mensaje de confirmación
SELECT 'Bases de datos creadas exitosamente: senselab_landlord, senselab_landlord_testing' AS message;
