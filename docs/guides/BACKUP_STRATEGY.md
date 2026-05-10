# Estrategia de Backups - Senselab Core API

## Descripción General

Este documento describe la estrategia de backups para la base de datos del sistema Senselab Core API, incluyendo procedimientos de backup, restauración y mejores prácticas.

## Ubicación de Scripts

```
database/backups/
├── backup.sh          # Script de backup automático
├── restore.sh         # Script de restauración
└── backup_*.sql.gz    # Archivos de backup (generados automáticamente)
```

## Configuración de Backups

### Características del Backup

- **Formato**: SQL comprimido con gzip (.sql.gz)
- **Tipo**: Backup completo de base de datos
- **Retención**: 7 días (configurable)
- **Incluye**:
  - Estructura de todas las tablas
  - Todos los datos
  - Stored procedures
  - Triggers
  - Events
  - Views

### Política de Retención

Por defecto, se conservan backups de los últimos **7 días**. Los backups más antiguos se eliminan automáticamente.

Para cambiar el período de retención, editar `RETENTION_DAYS` en `backup.sh`:

```bash
RETENTION_DAYS=7  # Cambiar a número deseado
```

## Uso de Scripts

### 1. Realizar Backup Manual

```bash
cd /home/dawnweaber/Workspace/Senselab_Core_API
./database/backups/backup.sh
```

**Salida esperada:**
```
[2024-01-15 12:00:00] Iniciando backup de base de datos: api_db
[2024-01-15 12:00:00] Servidor: mysql:3306
[2024-01-15 12:00:05] ✓ Backup completado exitosamente: backup_api_db_20240115_120000.sql.gz
[2024-01-15 12:00:05]   Tamaño: 2.3M
[2024-01-15 12:00:05] Limpiando backups antiguos...
```

### 2. Restaurar Backup

#### Opción A: Selección Interactiva

```bash
./database/backups/restore.sh
```

El script mostrará una lista de backups disponibles para seleccionar.

#### Opción B: Restaurar Archivo Específico

```bash
./database/backups/restore.sh backup_api_db_20240115_120000.sql.gz
```

**Proceso de restauración:**
1. Muestra información del backup
2. Solicita confirmación (escribir "si")
3. Crea backup de seguridad automáticamente
4. Restaura el backup seleccionado
5. Verifica la restauración

⚠️ **IMPORTANTE**: La restauración REEMPLAZA todos los datos actuales. El script crea un backup de seguridad antes de restaurar.

## Automatización con Cron

### Configurar Backup Diario

#### Para Servidor Linux

1. Editar crontab:
```bash
crontab -e
```

2. Agregar línea para backup diario a las 2:00 AM:
```cron
0 2 * * * /home/dawnweaber/Workspace/Senselab_Core_API/database/backups/backup.sh >> /home/dawnweaber/Workspace/Senselab_Core_API/storage/logs/backup.log 2>&1
```

3. Verificar que se agregó:
```bash
crontab -l
```

#### Para Docker

Agregar en `docker-compose.yml` un servicio de backup:

```yaml
services:
  backup:
    image: alpine:latest
    volumes:
      - ./database/backups:/backups
      - ./.env:/app/.env:ro
    environment:
      - BACKUP_SCHEDULE=0 2 * * *  # 2:00 AM diario
    command: |
      sh -c '
        apk add --no-cache mysql-client bash
        echo "$${BACKUP_SCHEDULE} /backups/backup.sh" | crontab -
        crond -f
      '
```

### Horarios Recomendados

| Ambiente | Horario | Frecuencia | Retención |
|----------|---------|------------|-----------|
| Producción | 2:00 AM | Diario | 30 días |
| Staging | 3:00 AM | Diario | 7 días |
| Desarrollo | 4:00 AM | Semanal | 3 días |

## Backups en Producción

### Estrategia de 3-2-1

Para producción, se recomienda seguir la regla **3-2-1**:

- **3** copias de tus datos
- **2** diferentes medios de almacenamiento
- **1** copia fuera del sitio (off-site)

#### Implementación Sugerida

1. **Backup Local** (database/backups/)
   - Backup automático diario
   - Retención: 7 días
   - Restauración rápida

2. **Backup en Almacenamiento Externo** (S3/DO Spaces)
   ```bash
   # Después de crear backup
   aws s3 cp backup_*.sql.gz s3://senselab-backups/database/
   ```
   - Retención: 30 días
   - Recuperación ante desastres

3. **Backup Off-Site** (Servidor diferente)
   ```bash
   # Rsync a servidor remoto
   rsync -avz database/backups/ usuario@backup-server:/backups/senselab-core/
   ```
   - Retención: 90 días
   - Protección contra fallas de infraestructura

### Script de Backup Remoto

Crear `database/backups/backup-remote.sh`:

```bash
#!/bin/bash
# Realizar backup y subir a almacenamiento remoto

# Ejecutar backup local
./database/backups/backup.sh

# Obtener backup más reciente
LATEST_BACKUP=$(ls -t database/backups/backup_*.sql.gz | head -1)

# Subir a S3 (requiere AWS CLI configurado)
aws s3 cp "$LATEST_BACKUP" "s3://senselab-backups/database/$(basename $LATEST_BACKUP)"

# O usar rsync a servidor remoto
# rsync -avz "$LATEST_BACKUP" usuario@backup-server:/backups/senselab-core/
```

## Monitoreo de Backups

### Verificar que los Backups se Ejecutan

1. **Revisar logs de cron:**
```bash
tail -f /var/log/cron
# o
tail -f storage/logs/backup.log
```

2. **Verificar backups recientes:**
```bash
ls -lht database/backups/backup_*.sql.gz | head -5
```

3. **Verificar tamaño de backups:**
```bash
du -sh database/backups/
```

### Alertas Recomendadas

Configure alertas para:
- ❌ Backup falla por 2 días consecutivos
- ⚠️ Tamaño de backup muy diferente al promedio (±30%)
- 💾 Espacio en disco < 20%
- 🕒 Backup toma más de 30 minutos

## Testing de Restauración

### Plan de Testing Trimestral

1. **Seleccionar backup aleatorio** del último mes
2. **Restaurar en ambiente de staging**
3. **Verificar integridad de datos:**
   - Contar registros en tablas críticas
   - Verificar relaciones foreign key
   - Probar funcionalidad crítica (login, reportes)
4. **Documentar resultados**

### Script de Verificación

```bash
#!/bin/bash
# database/backups/verify-backup.sh

BACKUP_FILE=$1

# Restaurar en base de datos temporal
createdb api_db_test
gunzip < "$BACKUP_FILE" | mysql api_db_test

# Verificar tablas
TABLE_COUNT=$(mysql api_db_test -sse "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = 'api_db_test'")

echo "Tablas restauradas: $TABLE_COUNT"

# Verificar datos críticos
USUARIO_COUNT=$(mysql api_db_test -sse "SELECT COUNT(*) FROM usuarios")
EMPRESA_COUNT=$(mysql api_db_test -sse "SELECT COUNT(*) FROM empresas")

echo "Usuarios: $USUARIO_COUNT"
echo "Empresas: $EMPRESA_COUNT"

# Limpiar
mysql -e "DROP DATABASE api_db_test"
```

## Recuperación ante Desastres

### Escenarios y Procedimientos

#### 1. Corrupción de Datos (Usuario borró datos por error)

```bash
# 1. Identificar momento del incidente
# 2. Seleccionar backup anterior al incidente
./database/backups/restore.sh backup_api_db_20240115_120000.sql.gz

# 3. Verificar datos restaurados
# 4. Notificar a usuarios
```

#### 2. Falla Total del Servidor

```bash
# 1. Levantar nuevo servidor
# 2. Instalar MySQL
# 3. Copiar backup más reciente
scp backup-server:/backups/senselab-core/backup_*.sql.gz ./

# 4. Restaurar
./database/backups/restore.sh backup_api_db_20240115_120000.sql.gz

# 5. Actualizar DNS/configuración
```

#### 3. Ransomware/Ataque

```bash
# 1. Aislar servidor comprometido
# 2. Analizar backup para verificar que no está comprometido
# 3. Levantar en infraestructura limpia
# 4. Restaurar último backup limpio
# 5. Cambiar todas las credenciales
```

### Tiempo de Recuperación Objetivo (RTO)

| Escenario | RTO | RPO |
|-----------|-----|-----|
| Corrupción de datos | 15 minutos | 1 día |
| Falla de servidor | 1 hora | 1 día |
| Desastre total | 4 horas | 1 día |

**RTO**: Recovery Time Objective (tiempo para restaurar servicio)
**RPO**: Recovery Point Objective (pérdida máxima de datos)

## Mejores Prácticas

### ✅ DO (Hacer)

- ✅ Automatizar backups diarios
- ✅ Verificar backups regularmente
- ✅ Probar restauraciones trimestralmente
- ✅ Mantener backups en múltiples ubicaciones
- ✅ Monitorear espacio en disco
- ✅ Documentar procedimientos de restauración
- ✅ Cifrar backups sensibles
- ✅ Rotar backups antiguos

### ❌ DON'T (No hacer)

- ❌ Confiar en un solo backup
- ❌ Nunca probar restauraciones
- ❌ Guardar backups solo en el mismo servidor
- ❌ Ignorar alertas de backup fallido
- ❌ Usar contraseñas en scripts (usar .env)
- ❌ Compartir backups sin cifrar

## Seguridad de Backups

### Cifrado de Backups

Para datos sensibles, cifrar backups antes de almacenar:

```bash
# Cifrar backup
gpg --symmetric --cipher-algo AES256 backup_api_db_20240115.sql.gz

# Descifrar para restaurar
gpg --decrypt backup_api_db_20240115.sql.gz.gpg | gunzip | mysql api_db
```

### Control de Acceso

- Backups solo accesibles por usuario root/admin
- Permisos 600 en archivos de backup
- Backups remotos en storage privado (no público)

```bash
chmod 600 database/backups/*.sql.gz
chown root:root database/backups/*.sql.gz
```

## Troubleshooting

### Backup Falla

**Síntoma**: Script de backup termina con error

**Soluciones**:
1. Verificar credenciales en .env
2. Verificar espacio en disco: `df -h`
3. Verificar permisos: `ls -l database/backups/`
4. Revisar logs de MySQL
5. Probar conexión manual: `mysql -h $DB_HOST -u $DB_USERNAME -p`

### Restauración Falla

**Síntoma**: Error al restaurar backup

**Soluciones**:
1. Verificar integridad del archivo: `gunzip -t backup.sql.gz`
2. Verificar permisos de usuario MySQL
3. Verificar espacio en disco
4. Intentar restauración manual:
   ```bash
   gunzip < backup.sql.gz | mysql api_db
   ```

### Backup Muy Grande

**Síntoma**: Backups ocupan mucho espacio

**Soluciones**:
1. Reducir `RETENTION_DAYS`
2. Limpiar tablas de logs/auditoría antiguas
3. Usar backup incremental (requiere setup adicional)
4. Comprimir con nivel más alto: `gzip -9`

## Contacto y Soporte

Para problemas con backups o recuperación:
- Email: deadmooncr@gmail.com
- Urgencias: Contactar administrador de sistemas

## Historial de Cambios

| Fecha | Versión | Cambios |
|-------|---------|---------|
| 2024-01-15 | 1.0 | Implementación inicial de estrategia de backups |
