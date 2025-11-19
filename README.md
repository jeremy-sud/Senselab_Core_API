# Ursol CAST API

<p align="center">
  <img src="https://ursol.com/assets/img/logo-ursol.png" width="400" alt="Sistemas Ursol Logo">
</p>

<p align="center">
  <strong>Desarrollado por Sistemas Ursol S.A.</strong><br>
  <em>Soluciones Tecnológicas con más de 30 años de experiencia</em>
</p>

<p align="center">
  <a href="https://github.com/SistemasUrsol/Ursol-CAST-API"><img src="https://img.shields.io/badge/GitHub-SistemasUrsol-blue" alt="GitHub"></a>
  <a href="https://ursol.com"><img src="https://img.shields.io/badge/Web-ursol.com-green" alt="Web"></a>
  <a href="https://ursol.net"><img src="https://img.shields.io/badge/Web-ursol.net-green" alt="Web Alt"></a>
  <a href="https://wa.me/50688687765"><img src="https://img.shields.io/badge/WhatsApp-Soporte-25D366" alt="WhatsApp"></a>
</p>

## 📋 Tabla de Contenidos

- [Acerca del Proyecto](#-acerca-del-proyecto)
- [Características Principales](#-características-principales)
- [Requisitos del Sistema](#-requisitos-del-sistema)
- [Instalación](#-instalación)
- [Configuración](#️-configuración)
- [Arquitectura](#-arquitectura)
- [Módulos del Sistema](#-módulos-del-sistema)
- [API Reference](#-api-reference)
- [Testing](#-testing)
- [Despliegue](#-despliegue)
- [Contribuir](#-contribuir)
- [Licencia](#-licencia)

## 🚀 Acerca del Proyecto

**Ursol CAST API** es un sistema ERP completo desarrollado por **Sistemas Ursol S.A.** con Laravel 11, diseñado específicamente para empresas costarricenses que requieren soluciones tecnológicas robustas y escalables.

### 🏢 Sobre Sistemas Ursol S.A.

Con **casi 30 años de experiencia** en el mercado costarricense, Sistemas Ursol S.A. es una empresa familiar que se distingue por su **ética inquebrantable**, **atención personalizada** y el **"Toque Humano"** en cada proyecto. Fundada y liderada por **Eduardo Alberto Ureña Solano**, quien aporta más de 35 años de experiencia en el sector tecnológico.

**Nuestros Servicios:**
- ✨ Soluciones Tecnológicas Empresariales
- 💻 Desarrollo de Software (ERP, CRM, Sistemas a Medida)
- 🎨 Diseño Web Profesional
- 🖼️ Diseño Gráfico Corporativo

### 🎯 Capacidades del Sistema

Este ERP proporciona gestión integral de:

- **Facturación Electrónica** (integración completa con DGT/Hacienda de Costa Rica)
- **Inventario Multi-Almacén** (control en tiempo real)
- **Contabilidad** (plan de cuentas, asientos, reportes financieros)
- **Recursos Humanos y Nómina** (gestión completa de empleados)
- **Gestión de Transporte** (módulo especializado para empresas de buses)
- **Multi-Tenancy** (soporte para múltiples empresas en una sola instalación)

El sistema está diseñado con las mejores prácticas de desarrollo, siguiendo los estándares de Laravel y con enfoque en escalabilidad, seguridad y facilidad de mantenimiento.

## ✨ Características Principales

### Facturación Electrónica
- ✅ Emisión de facturas electrónicas según normativa DGT
- ✅ Recepción y procesamiento de comprobantes electrónicos
- ✅ Gestión de consecutivos de facturación
- ✅ Integración con códigos CAByS
- ✅ Soporte para múltiples tipos de documento (Factura, Tiquete, Nota de Crédito/Débito)

### Inventario
- ✅ Control multi-almacén
- ✅ Seguimiento de stock en tiempo real
- ✅ Gestión de entradas y salidas de inventario
- ✅ Transferencias entre almacenes
- ✅ Kardex detallado por producto
- ✅ Categorización y clasificación de productos

### Contabilidad
- ✅ Plan de cuentas configurable
- ✅ Asientos contables automáticos y manuales
- ✅ Cuentas por cobrar y por pagar
- ✅ Caja chica
- ✅ Reportes financieros

### Recursos Humanos
- ✅ Gestión de empleados
- ✅ Cálculo de nómina
- ✅ Deducciones y bonificaciones
- ✅ Periodos de pago configurables

### Transporte
- ✅ Gestión de rutas y horarios
- ✅ Control de flota de buses
- ✅ Asignación de unidades
- ✅ Ventas de boletos

### Multi-Tenancy
- ✅ Soporte para múltiples empresas
- ✅ Aislamiento completo de datos
- ✅ Configuraciones independientes por tenant

## 💻 Requisitos del Sistema

- **PHP**: >= 8.2
- **Composer**: >= 2.5
- **Node.js**: >= 18.x
- **NPM**: >= 9.x
- **MySQL**: >= 8.0 o **MariaDB**: >= 10.6
- **Redis** (opcional, recomendado para producción)
- **Extensiones PHP requeridas**:
  - OpenSSL
  - PDO
  - Mbstring
  - Tokenizer
  - XML
  - Ctype
  - JSON
  - BCMath
  - Fileinfo
  - GD o Imagick (para procesamiento de imágenes)

## 🔧 Instalación

### 1. Clonar el Repositorio

```bash
# Desde GitHub Organization oficial
git clone https://github.com/SistemasUrsol/Ursol-CAST-API.git
cd Ursol-CAST-API

# O desde Ursol Reposit for Developers
# Consulta https://sites.google.com/view/repdevursol/home/repositorio para acceso
```

### 2. Instalar Dependencias

```bash
# Dependencias de PHP
composer install

# Dependencias de Node.js
npm install
```

### 3. Configurar Variables de Entorno

```bash
# Copiar archivo de ejemplo
cp .env.example .env

# Generar key de aplicación
php artisan key:generate
```

### 4. Configurar Base de Datos

Edita el archivo `.env` con tus credenciales:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=api_db
DB_USERNAME=nuevo_usuario
DB_PASSWORD=nueva_contraseña
```

### 5. Ejecutar Migraciones

```bash
# Migraciones del landlord (sistema central)
php artisan migrate --path=database/migrations/landlord

# Migraciones de tenants (se ejecutarán automáticamente al crear empresas)
php artisan migrate
```

### 6. Compilar Assets

```bash
# Desarrollo
npm run dev

# Producción
npm run build
```

### 7. Iniciar Servidor de Desarrollo

```bash
php artisan serve
```

El sistema estará disponible en `http://localhost:8000`

## ⚙️ Configuración

### Configuración de Multi-Tenancy

El sistema utiliza el paquete `spatie/laravel-multitenancy`. La configuración se encuentra en [config/multitenancy.php](config/multitenancy.php).

### Configuración de Base de Datos

Cada tenant (empresa) tiene su propia base de datos. El nombre se genera automáticamente:

```
tenant_{empresa_id}_{nombre_sanitizado}
```

### Configuración de Facturación Electrónica

Para habilitar la facturación electrónica, configura:

```env
HACIENDA_API_URL=https://api.comprobanteselectronicos.go.cr
HACIENDA_API_TOKEN=tu_token_de_hacienda
```

Cada empresa debe cargar su certificado de firma digital en el sistema.

## 🏗️ Arquitectura

### Estructura de Directorios

```
ursol-cast-api/
├── app/
│   ├── Http/
│   │   └── Controllers/
│   │       └── API/          # Controladores de API
│   ├── Models/               # Modelos Eloquent
│   ├── Providers/            # Service Providers
│   └── Traits/               # Traits reutilizables
│       └── BelongsToTenant.php
├── config/                   # Archivos de configuración
├── database/
│   ├── migrations/
│   │   ├── landlord/        # Migraciones del sistema central
│   │   └── tenant/          # Migraciones de tenants
│   └── factories/           # Factories para testing
├── routes/
│   ├── api.php              # Rutas de API
│   └── web.php              # Rutas web
└── tests/                   # Tests automatizados
```

### Patrón de Diseño

El proyecto sigue el patrón **MVC** (Model-View-Controller) con algunas extensiones:

- **Models**: Lógica de negocio y relaciones de datos
- **Controllers**: Manejo de requests HTTP
- **Traits**: Comportamiento compartido (ej: `BelongsToTenant`)
- **Service Providers**: Configuración de servicios

### Base de Datos

#### Arquitectura Multi-Tenant

- **Base de datos central (landlord)**: Almacena información de empresas/tenants
- **Bases de datos por tenant**: Cada empresa tiene su propia base de datos

#### Convenciones de Nomenclatura

- **Tablas**: plural, snake_case (ej: `ordenes_compra`)
- **Columnas de timestamps**: `creado_en`, `actualizado_en`
- **Soft deletes**: columna `eliminado` (boolean)
- **Estado activo**: columna `activo` (boolean)

## 📦 Módulos del Sistema

### 1. Gestión de Empresas
- **Modelos**: [`Empresa`](app/Models/Empresa.php), [`Sucursal`](app/Models/Sucursal.php), [`Configuracion`](app/Models/Configuracion.php)
- **Funcionalidades**: Registro de empresas, gestión de sucursales, configuraciones personalizadas

### 2. Inventario
- **Modelos**: [`Producto`](app/Models/Producto.php), [`Almacen`](app/Models/Almacen.php), [`EntradaInventario`](app/Models/EntradaInventario.php), [`SalidaInventario`](app/Models/SalidaInventario.php)
- **Funcionalidades**: Control de stock, movimientos de inventario, categorización

### 3. Ventas
- **Modelos**: [`Venta`](app/Models/Venta.php), [`Cliente`](app/Models/Cliente.php), [`CuentaPorCobrar`](app/Models/CuentaPorCobrar.php)
- **Funcionalidades**: Registro de ventas, gestión de clientes, cuentas por cobrar

### 4. Compras
- **Modelos**: [`OrdenCompra`](app/Models/OrdenCompra.php), [`CuentaPorPagar`](app/Models/CuentaPorPagar.php), [`ComprobanteRecibidoElectronico`](app/Models/ComprobanteRecibidoElectronico.php)
- **Funcionalidades**: Órdenes de compra, cuentas por pagar, recepción de comprobantes electrónicos

### 5. Contabilidad
- **Modelos**: [`CuentaContable`](app/Models/CuentaContable.php), [`AsientoContable`](app/Models/AsientoContable.php), [`CajaChica`](app/Models/CajaChica.php)
- **Funcionalidades**: Plan de cuentas, asientos contables, caja chica

### 6. Recursos Humanos
- **Modelos**: [`Empleado`](app/Models/Empleado.php), [`PeriodoNomina`](app/Models/PeriodoNomina.php)
- **Funcionalidades**: Gestión de empleados, cálculo de nómina

### 7. Transporte
- **Modelos**: [`BusUnidad`](app/Models/BusUnidad.php), [`HorarioRuta`](app/Models/HorarioRuta.php)
- **Funcionalidades**: Gestión de flota, rutas y horarios

### 8. Facturación Electrónica
- **Modelos**: [`ConsecutivoFe`](app/Models/ConsecutivoFe.php), [`Cabys`](app/Models/Cabys.php)
- **Funcionalidades**: Emisión de facturas electrónicas, gestión de consecutivos, códigos CAByS

## 🔌 API Reference

### Autenticación

El sistema utiliza **Laravel Sanctum** para autenticación de API.

#### Login
```http
POST /api/login
Content-Type: application/json

{
  "email": "usuario@ejemplo.com",
  "password": "contraseña"
}
```

#### Registro
```http
POST /api/register
Content-Type: application/json

{
  "name": "Nombre Usuario",
  "email": "usuario@ejemplo.com",
  "password": "contraseña",
  "password_confirmation": "contraseña"
}
```

### Endpoints Principales

Todos los endpoints requieren autenticación excepto login y register.

#### Almacenes
```http
GET    /api/almacenes           # Listar almacenes
POST   /api/almacenes           # Crear almacén
GET    /api/almacenes/{id}      # Ver almacén
PUT    /api/almacenes/{id}      # Actualizar almacén
DELETE /api/almacenes/{id}      # Eliminar almacén
```

#### Productos
```http
GET    /api/productos           # Listar productos
POST   /api/productos           # Crear producto
GET    /api/productos/{id}      # Ver producto
PUT    /api/productos/{id}      # Actualizar producto
DELETE /api/productos/{id}      # Eliminar producto
```

#### Ventas
```http
GET    /api/ventas              # Listar ventas
POST   /api/ventas              # Crear venta
GET    /api/ventas/{id}         # Ver venta
PUT    /api/ventas/{id}         # Actualizar venta
DELETE /api/ventas/{id}         # Eliminar venta
```

#### Clientes
```http
GET    /api/clientes            # Listar clientes
POST   /api/clientes            # Crear cliente
GET    /api/clientes/{id}       # Ver cliente
PUT    /api/clientes/{id}       # Actualizar cliente
DELETE /api/clientes/{id}       # Eliminar cliente
```

### Headers Requeridos

```http
Authorization: Bearer {token}
Content-Type: application/json
Accept: application/json
```

## 🧪 Testing

### Ejecutar Tests

```bash
# Todos los tests
php artisan test

# Tests específicos
php artisan test --filter NombreDelTest

# Con cobertura
php artisan test --coverage
```

### Estructura de Tests

```
tests/
├── Feature/              # Tests de integración
│   ├── AlmacenTest.php
│   ├── ProductoTest.php
│   └── VentaTest.php
└── Unit/                 # Tests unitarios
    ├── Models/
    └── Services/
```

## 🚀 Despliegue

### Preparación para Producción

1. **Optimizar Configuración**
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

2. **Compilar Assets**
```bash
npm run build
```

3. **Configurar Variables de Entorno**
```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://tu-dominio.com

# Configurar Redis para cache y sesiones
CACHE_STORE=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis
```

4. **Configurar Permisos**
```bash
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

### Servidor Web

#### Nginx (Recomendado)

```nginx
server {
    listen 80;
    server_name tu-dominio.com;
    root /ruta/al/proyecto/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

### Tareas Programadas

Configurar cron para ejecutar el scheduler de Laravel:

```bash
* * * * * cd /ruta/al/proyecto && php artisan schedule:run >> /dev/null 2>&1
```

### Supervisord para Queues

```ini
[program:ursol-cast-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /ruta/al/proyecto/artisan queue:work redis --sleep=3 --tries=3
autostart=true
autorestart=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/ruta/al/proyecto/storage/logs/worker.log
```

## 🤝 Contribuir

Las contribuciones son bienvenidas. Por favor:

1. Fork el proyecto
2. Crea una rama para tu feature (`git checkout -b feature/AmazingFeature`)
3. Commit tus cambios (`git commit -m 'Add some AmazingFeature'`)
4. Push a la rama (`git push origin feature/AmazingFeature`)
5. Abre un Pull Request

### Estándares de Código

- Seguir [PSR-12](https://www.php-fig.org/psr/psr-12/) para código PHP
- Usar nombres descriptivos en español para variables y métodos
- Documentar funciones y clases con PHPDoc
- Escribir tests para nuevas funcionalidades

## 📄 Licencia

Este proyecto está licenciado bajo la Licencia MIT. Ver archivo [LICENSE](LICENSE) para más detalles.

## 📞 Soporte y Contacto

Para soporte técnico, consultas y asistencia:

- **📧 Email Corporativo**: [sistemas@ursol.com](mailto:sistemas@ursol.com)
- **📧 Email Técnico/Desarrollo**: [deadmooncr@gmail.com](mailto:deadmooncr@gmail.com)
- **💬 WhatsApp**: [+506 8868-7765](https://wa.me/50688687765)
- **🌐 Sitio Web**: [ursol.com](https://ursol.com) | [ursol.net](https://ursol.net)
- **📚 Repositorio Oficial**: [Ursol Reposit for Developers](https://sites.google.com/view/repdevursol/home/repositorio) - Plataforma de desarrollo, distribución y documentación
- **🐙 GitHub Organization**: [SistemasUrsol](https://github.com/orgs/SistemasUrsol)
- **👨‍💻 Desarrollador Principal**: [Jeremy Arias Solano](https://github.com/jeremy-sud)
- **🐛 Issues**: [GitHub Issues](https://github.com/SistemasUrsol/Ursol-CAST-API/issues)

## 🙏 Agradecimientos

- [Laravel Framework](https://laravel.com) - El framework PHP más elegante
- [Spatie Laravel Multitenancy](https://github.com/spatie/laravel-multitenancy) - Solución robusta de multi-tenancy
- Comunidad de desarrollo Laravel Costa Rica
- Nuestros clientes que confían en Sistemas Ursol S.A.

---

<p align="center">
  <strong>Desarrollado con ❤️ y el "Toque Humano" por</strong><br>
  <a href="https://ursol.com"><strong>Sistemas Ursol S.A.</strong></a><br>
  <em>Costa Rica | 30 años de experiencia tecnológica</em><br><br>
  <strong>Fundador y Visionario:</strong> Eduardo Alberto Ureña Solano<br>
  <strong>Desarrollador Principal:</strong> <a href="https://github.com/jeremy-sud">Jeremy Arias Solano</a><br><br>
  <sub>© 2025 Sistemas Ursol S.A. - Todos los derechos reservados</sub>
</p>
