# 📦 Resumen para Nuevos Colaboradores

## ✅ ¿Cómo instalar Senselab Core API en tu laptop?

**¡Es muy fácil! NO necesitas que te pasen la base de datos por privado.**

---

## 🚀 Opción 1: Instalación Automática (Recomendada)

### Para Linux/Mac:
```bash
git clone https://github.com/SenseLab-dev/Senselab_Core_API.git
cd Senselab_Core_API
./scripts/install.sh
```

### Para Windows (PowerShell):
```powershell
git clone https://github.com/SenseLab-dev/Senselab_Core_API.git
cd Senselab_Core_API
.\install.ps1
```

**El script hace TODO por ti:**
- ✅ Verifica que tengas PHP, Composer y MySQL
- ✅ Instala dependencias automáticamente
- ✅ Crea las bases de datos
- ✅ Ejecuta migraciones (66 tablas)
- ✅ Carga seeders (112 registros de datos maestros y demo)
- ✅ Configura todo el ambiente

**Tiempo estimado:** 5-10 minutos

---

## 📖 Opción 2: Instalación Manual

Sigue la guía paso a paso: **[INSTALLATION_GUIDE.md](INSTALLATION_GUIDE.md)**

Esta guía incluye:
- ✅ Requisitos previos
- ✅ 11 pasos detallados con ejemplos
- ✅ Verificación de instalación
- ✅ Troubleshooting completo
- ✅ Checklist para no olvidar nada

---

## 🔑 Credenciales Pre-configuradas

Después de la instalación, ya tendrás:

**Usuario Administrador:**
```
Email:    admin@senselab.com
Password: admin123
Permisos: 68 (acceso completo)
```

**Empresa Demo:**
```
Nombre:  Senselab
Cédula:  3-101-123456
```

---

## 📊 ¿Qué datos se cargan automáticamente?

Los **seeders** cargan automáticamente 112 registros:

### Datos Maestros (96 registros):
- 2 Regímenes Tributarios
- 6 Formas de Pago
- 8 Tipos de Cuentas Contables
- 11 Unidades de Medida
- 68 Permisos del sistema
- 7 Roles (Administrador, Gerente, Contador, etc.)

### Datos de Prueba (16 registros):
- 7 Cargos de empleados
- 1 Empresa demo completa
- 1 Sucursal
- 1 Usuario admin con todos los permisos

**¡Todo listo para empezar a trabajar!**

---

## 🧪 Verificar que Todo Funciona

### 1. Iniciar servidor
```bash
php artisan serve
```

### 2. Probar login
```bash
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@senselab.com","password":"admin123"}'
```

### 3. Ver documentación Swagger
```
http://localhost:8000/api/documentation
```

### 4. Ejecutar tests
```bash
php artisan test
```

---

## 📚 Documentación Disponible

Una vez instalado, puedes consultar:

| Archivo | Descripción |
|---------|-------------|
| [README.md](README.md) | Documentación principal del proyecto |
| [INSTALLATION_GUIDE.md](INSTALLATION_GUIDE.md) | Guía detallada de instalación |
| [API_DOCUMENTATION.md](API_DOCUMENTATION.md) | 413 endpoints documentados |
| [TESTING_GUIDE.md](TESTING_GUIDE.md) | Cómo escribir y ejecutar tests |
| [ESTADO_ACTUAL_PROYECTO.md](ESTADO_ACTUAL_PROYECTO.md) | Estado actual y roadmap |
| [CONTRIBUTING.md](CONTRIBUTING.md) | Guía para contribuir al proyecto |

---

## ❓ Preguntas Frecuentes

### ¿Necesito que me pasen la base de datos por privado?
**NO.** Los seeders crean automáticamente todos los datos necesarios.

### ¿Qué pasa si ya tengo datos en mi base de datos?
Puedes usar `php artisan migrate:fresh --seed` para resetear todo.

### ¿Cómo actualizo mi copia local con los últimos cambios?
```bash
git pull origin main
composer install
php artisan migrate
```

### ¿Y si tengo problemas?
1. Revisa la sección de **Troubleshooting** en [INSTALLATION_GUIDE.md](INSTALLATION_GUIDE.md)
2. Crea un Issue en GitHub con los detalles

---

## 🎯 Próximos Pasos

Después de instalar:

1. ✅ Explora la API con Swagger: `http://localhost:8000/api/documentation`
2. ✅ Revisa los controladores en `app/Http/Controllers/API/`
3. ✅ Lee la documentación de endpoints
4. ✅ Crea tu rama para trabajar: `git checkout -b feature/mi-feature`
5. ✅ Escribe tests para tus cambios
6. ✅ Haz commit y crea Pull Request

---

## 🔧 Requisitos Mínimos

- **PHP:** 8.2 o superior
- **MySQL:** 8.0 o superior
- **Composer:** 2.x
- **Git:** 2.x

---

## 📞 Soporte

**Senselab**  
📧 Email: soporte@senselab.com  
🌐 Web: https://senselab.com

---

**¡Bienvenido al equipo de desarrollo! 🎉**
