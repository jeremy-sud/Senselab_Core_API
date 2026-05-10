-- ============================================================================
-- SCRIPT DE VALIDACIÓN DE INTEGRIDAD DE BASE DE DATOS
-- ============================================================================
-- Proyecto: Senselab Core API
-- Base de Datos: api_db
-- Fecha: 2025-11-25
-- Descripción: Valida PKs, FKs, índices y detecta problemas de integridad
-- ============================================================================

-- CONFIGURACIÓN
SET @database_name = 'api_db';
SET @show_details = 1; -- 1 para mostrar detalles, 0 para solo resumen

-- ============================================================================
-- 1. VALIDACIÓN DE PRIMARY KEYS
-- ============================================================================

SELECT 'VALIDACIÓN DE PRIMARY KEYS' AS 'SECCIÓN';
SELECT '===========================================' AS '';

-- Tablas sin Primary Key (NO debería haber ninguna)
SELECT 
    'Tablas SIN Primary Key' AS 'VALIDACIÓN',
    COUNT(*) AS 'CANTIDAD'
FROM information_schema.TABLES t
LEFT JOIN information_schema.TABLE_CONSTRAINTS tc 
    ON t.TABLE_SCHEMA = tc.TABLE_SCHEMA 
    AND t.TABLE_NAME = tc.TABLE_NAME 
    AND tc.CONSTRAINT_TYPE = 'PRIMARY KEY'
WHERE t.TABLE_SCHEMA = @database_name
    AND t.TABLE_TYPE = 'BASE TABLE'
    AND tc.CONSTRAINT_NAME IS NULL;

-- Detalle de tablas sin PK (si las hay)
SELECT 
    t.TABLE_NAME AS 'TABLA SIN PK'
FROM information_schema.TABLES t
LEFT JOIN information_schema.TABLE_CONSTRAINTS tc 
    ON t.TABLE_SCHEMA = tc.TABLE_SCHEMA 
    AND t.TABLE_NAME = tc.TABLE_NAME 
    AND tc.CONSTRAINT_TYPE = 'PRIMARY KEY'
WHERE t.TABLE_SCHEMA = @database_name
    AND t.TABLE_TYPE = 'BASE TABLE'
    AND tc.CONSTRAINT_NAME IS NULL
    AND @show_details = 1;

-- ============================================================================
-- 2. VALIDACIÓN DE FOREIGN KEYS
-- ============================================================================

SELECT '' AS '';
SELECT 'VALIDACIÓN DE FOREIGN KEYS' AS 'SECCIÓN';
SELECT '===========================================' AS '';

-- Total de Foreign Keys
SELECT 
    'Total Foreign Keys en BD' AS 'VALIDACIÓN',
    COUNT(*) AS 'CANTIDAD'
FROM information_schema.KEY_COLUMN_USAGE
WHERE TABLE_SCHEMA = @database_name
    AND REFERENCED_TABLE_NAME IS NOT NULL;

-- Foreign Keys a empresa_id (debería ser 36+)
SELECT 
    'Foreign Keys a empresas.id' AS 'VALIDACIÓN',
    COUNT(*) AS 'CANTIDAD'
FROM information_schema.KEY_COLUMN_USAGE
WHERE TABLE_SCHEMA = @database_name
    AND COLUMN_NAME = 'empresa_id'
    AND REFERENCED_TABLE_NAME = 'empresas';

-- Tablas con empresa_id SIN Foreign Key (NO debería haber)
SELECT 
    'Tablas con empresa_id SIN FK' AS 'VALIDACIÓN',
    COUNT(DISTINCT c.TABLE_NAME) AS 'CANTIDAD'
FROM information_schema.COLUMNS c
LEFT JOIN information_schema.KEY_COLUMN_USAGE kcu
    ON c.TABLE_SCHEMA = kcu.TABLE_SCHEMA
    AND c.TABLE_NAME = kcu.TABLE_NAME
    AND c.COLUMN_NAME = kcu.COLUMN_NAME
    AND kcu.REFERENCED_TABLE_NAME = 'empresas'
WHERE c.TABLE_SCHEMA = @database_name
    AND c.COLUMN_NAME = 'empresa_id'
    AND kcu.CONSTRAINT_NAME IS NULL;

-- Detalle de tablas con empresa_id sin FK
SELECT DISTINCT
    c.TABLE_NAME AS 'TABLA',
    c.COLUMN_NAME AS 'COLUMNA',
    'FALTA FK a empresas' AS 'PROBLEMA'
FROM information_schema.COLUMNS c
LEFT JOIN information_schema.KEY_COLUMN_USAGE kcu
    ON c.TABLE_SCHEMA = kcu.TABLE_SCHEMA
    AND c.TABLE_NAME = kcu.TABLE_NAME
    AND c.COLUMN_NAME = kcu.COLUMN_NAME
    AND kcu.REFERENCED_TABLE_NAME = 'empresas'
WHERE c.TABLE_SCHEMA = @database_name
    AND c.COLUMN_NAME = 'empresa_id'
    AND kcu.CONSTRAINT_NAME IS NULL
    AND @show_details = 1;

-- ============================================================================
-- 3. VALIDACIÓN DE ÍNDICES
-- ============================================================================

SELECT '' AS '';
SELECT 'VALIDACIÓN DE ÍNDICES' AS 'SECCIÓN';
SELECT '===========================================' AS '';

-- Total de índices por tipo
SELECT 
    CASE 
        WHEN NON_UNIQUE = 0 THEN 'Índices ÚNICOS'
        WHEN INDEX_NAME = 'PRIMARY' THEN 'PRIMARY KEYS'
        ELSE 'Índices SIMPLES'
    END AS 'TIPO',
    COUNT(DISTINCT CONCAT(TABLE_NAME, '.', INDEX_NAME)) AS 'CANTIDAD'
FROM information_schema.STATISTICS
WHERE TABLE_SCHEMA = @database_name
GROUP BY 
    CASE 
        WHEN NON_UNIQUE = 0 THEN 'Índices ÚNICOS'
        WHEN INDEX_NAME = 'PRIMARY' THEN 'PRIMARY KEYS'
        ELSE 'Índices SIMPLES'
    END;

-- Tablas con empresa_id SIN índice (NO debería haber)
SELECT 
    'Tablas con empresa_id SIN índice' AS 'VALIDACIÓN',
    COUNT(DISTINCT c.TABLE_NAME) AS 'CANTIDAD'
FROM information_schema.COLUMNS c
LEFT JOIN information_schema.STATISTICS s
    ON c.TABLE_SCHEMA = s.TABLE_SCHEMA
    AND c.TABLE_NAME = s.TABLE_NAME
    AND c.COLUMN_NAME = s.COLUMN_NAME
WHERE c.TABLE_SCHEMA = @database_name
    AND c.COLUMN_NAME = 'empresa_id'
    AND s.INDEX_NAME IS NULL;

-- Detalle de empresa_id sin índice
SELECT DISTINCT
    c.TABLE_NAME AS 'TABLA',
    c.COLUMN_NAME AS 'COLUMNA',
    'FALTA ÍNDICE' AS 'PROBLEMA'
FROM information_schema.COLUMNS c
LEFT JOIN information_schema.STATISTICS s
    ON c.TABLE_SCHEMA = s.TABLE_SCHEMA
    AND c.TABLE_NAME = s.TABLE_NAME
    AND c.COLUMN_NAME = s.COLUMN_NAME
WHERE c.TABLE_SCHEMA = @database_name
    AND c.COLUMN_NAME = 'empresa_id'
    AND s.INDEX_NAME IS NULL
    AND @show_details = 1;

-- Índices compuestos (multi-columna)
SELECT 
    'Índices Compuestos (2+ columnas)' AS 'VALIDACIÓN',
    COUNT(DISTINCT CONCAT(TABLE_NAME, '.', INDEX_NAME)) AS 'CANTIDAD'
FROM information_schema.STATISTICS
WHERE TABLE_SCHEMA = @database_name
    AND INDEX_NAME != 'PRIMARY'
GROUP BY TABLE_NAME, INDEX_NAME
HAVING COUNT(COLUMN_NAME) > 1;

-- ============================================================================
-- 4. VALIDACIÓN DE INTEGRIDAD REFERENCIAL
-- ============================================================================

SELECT '' AS '';
SELECT 'VALIDACIÓN DE INTEGRIDAD REFERENCIAL' AS 'SECCIÓN';
SELECT '===========================================' AS '';

-- Registros huérfanos en empresas (empresas eliminadas con registros)
-- NOTA: Esto solo detecta si existen, no muestra registros por privacidad

-- Verificar si hay registros en tablas con empresa_id que no existe
SET @check_orphans = '
SELECT 
    ''%TABLE%'' AS TABLA,
    COUNT(*) AS HUERFANOS
FROM %TABLE% t
LEFT JOIN empresas e ON t.empresa_id = e.id
WHERE e.id IS NULL
HAVING COUNT(*) > 0';

-- Esta query dinâmica requiere ejecución manual tabla por tabla
-- Para automatizar, usar un procedimiento almacenado

SELECT 
    'VERIFICACIÓN MANUAL REQUERIDA' AS 'VALIDACIÓN',
    'Ejecutar query por tabla para detectar huérfanos' AS 'ACCIÓN';

-- Ejemplo de query manual para una tabla:
-- SELECT 'ventas' AS TABLA, COUNT(*) AS HUERFANOS
-- FROM ventas t LEFT JOIN empresas e ON t.empresa_id = e.id
-- WHERE e.id IS NULL HAVING COUNT(*) > 0;

-- ============================================================================
-- 5. VALIDACIÓN DE TIPOS DE DATOS (PK vs FK)
-- ============================================================================

SELECT '' AS '';
SELECT 'VALIDACIÓN DE TIPOS DE DATOS' AS 'SECCIÓN';
SELECT '===========================================' AS '';

-- Verificar inconsistencias entre PK y FK (ejemplo: int vs bigint)
SELECT 
    kcu.TABLE_NAME AS 'TABLA_FK',
    kcu.COLUMN_NAME AS 'COLUMNA_FK',
    kcu.REFERENCED_TABLE_NAME AS 'TABLA_PK',
    kcu.REFERENCED_COLUMN_NAME AS 'COLUMNA_PK',
    c1.DATA_TYPE AS 'TIPO_FK',
    c2.DATA_TYPE AS 'TIPO_PK',
    CASE 
        WHEN c1.DATA_TYPE != c2.DATA_TYPE THEN '⚠️ INCONSISTENCIA'
        ELSE '✅ OK'
    END AS 'ESTADO'
FROM information_schema.KEY_COLUMN_USAGE kcu
JOIN information_schema.COLUMNS c1 
    ON kcu.TABLE_SCHEMA = c1.TABLE_SCHEMA
    AND kcu.TABLE_NAME = c1.TABLE_NAME
    AND kcu.COLUMN_NAME = c1.COLUMN_NAME
JOIN information_schema.COLUMNS c2
    ON kcu.REFERENCED_TABLE_SCHEMA = c2.TABLE_SCHEMA
    AND kcu.REFERENCED_TABLE_NAME = c2.TABLE_NAME
    AND kcu.REFERENCED_COLUMN_NAME = c2.COLUMN_NAME
WHERE kcu.TABLE_SCHEMA = @database_name
    AND kcu.REFERENCED_TABLE_NAME IS NOT NULL
    AND c1.DATA_TYPE != c2.DATA_TYPE
    AND @show_details = 1;

-- ============================================================================
-- 6. ÍNDICES POTENCIALMENTE INNECESARIOS
-- ============================================================================

SELECT '' AS '';
SELECT 'ANÁLISIS DE ÍNDICES REDUNDANTES' AS 'SECCIÓN';
SELECT '===========================================' AS '';

-- Índices redundantes (columna ya cubierta por índice compuesto)
-- Ejemplo: si existe idx(empresa_id, fecha), el idx(empresa_id) puede ser redundante
-- NOTA: Esta validación es compleja y requiere análisis manual

SELECT 
    'ANÁLISIS MANUAL REQUERIDO' AS 'VALIDACIÓN',
    'Revisar índices compuestos vs simples para detectar redundancia' AS 'ACCIÓN';

-- ============================================================================
-- 7. RESUMEN FINAL
-- ============================================================================

SELECT '' AS '';
SELECT 'RESUMEN FINAL DE VALIDACIÓN' AS 'SECCIÓN';
SELECT '===========================================' AS '';

SELECT 
    'Total de Tablas' AS 'MÉTRICA',
    COUNT(*) AS 'VALOR'
FROM information_schema.TABLES
WHERE TABLE_SCHEMA = @database_name
    AND TABLE_TYPE = 'BASE TABLE'

UNION ALL

SELECT 
    'Tablas con Primary Key',
    COUNT(DISTINCT TABLE_NAME)
FROM information_schema.TABLE_CONSTRAINTS
WHERE TABLE_SCHEMA = @database_name
    AND CONSTRAINT_TYPE = 'PRIMARY KEY'

UNION ALL

SELECT 
    'Total de Foreign Keys',
    COUNT(*)
FROM information_schema.KEY_COLUMN_USAGE
WHERE TABLE_SCHEMA = @database_name
    AND REFERENCED_TABLE_NAME IS NOT NULL

UNION ALL

SELECT 
    'Foreign Keys a empresas',
    COUNT(*)
FROM information_schema.KEY_COLUMN_USAGE
WHERE TABLE_SCHEMA = @database_name
    AND COLUMN_NAME = 'empresa_id'
    AND REFERENCED_TABLE_NAME = 'empresas'

UNION ALL

SELECT 
    'Total de Índices (excl. PK)',
    COUNT(DISTINCT CONCAT(TABLE_NAME, '.', INDEX_NAME))
FROM information_schema.STATISTICS
WHERE TABLE_SCHEMA = @database_name
    AND INDEX_NAME != 'PRIMARY'

UNION ALL

SELECT 
    'Índices Compuestos',
    COUNT(*)
FROM (
    SELECT TABLE_NAME, INDEX_NAME
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = @database_name
        AND INDEX_NAME != 'PRIMARY'
    GROUP BY TABLE_NAME, INDEX_NAME
    HAVING COUNT(COLUMN_NAME) > 1
) AS composite_indexes;

-- ============================================================================
-- FIN DEL SCRIPT
-- ============================================================================

SELECT '' AS '';
SELECT 'VALIDACIÓN COMPLETADA ✅' AS '';
SELECT 'Revisa los resultados anteriores para identificar problemas' AS '';
SELECT 'Si todas las validaciones muestran 0 problemas, la BD está ÓPTIMA' AS '';
