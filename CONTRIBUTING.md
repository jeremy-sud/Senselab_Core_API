# Guía de Contribución - Ursol CAST API

¡Gracias por tu interés en contribuir al proyecto Ursol CAST API desarrollado por **Sistemas Ursol S.A.**!

## 🏢 Sobre Sistemas Ursol S.A.

Somos una empresa familiar costarricense con **casi 30 años de experiencia** en el sector tecnológico. Nos caracterizamos por nuestra **ética inquebrantable**, **atención personalizada** y el **"Toque Humano"** en cada proyecto.

- **Fundador**: Eduardo Alberto Ureña Solano (35+ años de experiencia)
- **Desarrollador Principal**: Jeremy Arias Solano
- **Contacto**: sistemas@ursol.com | +506 8868-7765

## 🤝 Cómo Contribuir

### 1. Fork del Repositorio

```bash
# Fork desde GitHub
git clone https://github.com/SistemasUrsol/Ursol-CAST-API.git
cd Ursol-CAST-API

# Agregar remote upstream
git remote add upstream https://github.com/SistemasUrsol/Ursol-CAST-API.git
```

### 2. Crear una Rama de Feature

```bash
# Actualizar main
git checkout main
git pull upstream main

# Crear nueva rama
git checkout -b feature/nombre-descriptivo
# o
git checkout -b fix/descripcion-del-fix
```

### 3. Realizar Cambios

Asegúrate de seguir nuestros estándares:

#### Estándares de Código

- **PSR-12**: Seguir estándar PSR-12 para código PHP
- **Nombres en Español**: Variables, métodos y clases en español
- **Comentarios**: Documentar funciones con PHPDoc
- **Testing**: Escribir tests para nuevas funcionalidades

#### Ejemplo de Código

```php
<?php

namespace App\Services;

/**
 * Servicio para gestión de facturación electrónica
 * 
 * @author Jeremy Arias Solano <deadmooncr@gmail.com>
 * @copyright 2025 Sistemas Ursol S.A.
 */
class FacturacionElectronicaService
{
    /**
     * Genera una factura electrónica
     * 
     * @param array $datos Datos de la factura
     * @return array Factura generada
     * @throws \Exception Si hay error en la generación
     */
    public function generarFactura(array $datos): array
    {
        // Implementación
    }
}
```

### 4. Commit de Cambios

Usamos commits descriptivos en español:

```bash
git add .
git commit -m "feat: Agregar validación de identificación en clientes"
# o
git commit -m "fix: Corregir cálculo de impuestos en ventas"
# o
git commit -m "docs: Actualizar documentación de API"
```

**Prefijos de Commit:**
- `feat:` - Nueva funcionalidad
- `fix:` - Corrección de bug
- `docs:` - Cambios en documentación
- `style:` - Formato, espacios, etc.
- `refactor:` - Refactorización de código
- `test:` - Agregar o modificar tests
- `chore:` - Mantenimiento, dependencias

### 5. Ejecutar Tests

```bash
# Ejecutar todos los tests
php artisan test

# Con cobertura
php artisan test --coverage

# Tests específicos
php artisan test --filter NombreDelTest
```

### 6. Push y Pull Request

```bash
# Push a tu fork
git push origin feature/nombre-descriptivo
```

Luego crea un Pull Request en GitHub con:

**Título descriptivo:**
```
feat: Implementar módulo de facturación electrónica para DGT
```

**Descripción completa:**
```markdown
## Descripción
Implementa la integración completa con la API de DGT de Costa Rica para
emisión y recepción de comprobantes electrónicos.

## Cambios realizados
- [ ] Creación de servicios de facturación
- [ ] Integración con API de Hacienda
- [ ] Validación de certificados digitales
- [ ] Tests de integración

## Checklist
- [x] Código sigue PSR-12
- [x] Tests escritos y pasando
- [x] Documentación actualizada
- [x] Sin conflictos con main

## Screenshots (si aplica)
[Adjuntar capturas de pantalla]
```

## 📋 Checklist antes de PR

- [ ] El código sigue PSR-12
- [ ] Variables y métodos en español
- [ ] PHPDoc en funciones públicas
- [ ] Tests escritos y pasando
- [ ] Documentación actualizada
- [ ] Sin conflictos con main
- [ ] Commits descriptivos

## 🧪 Testing

Todos los PRs deben incluir tests:

```php
<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Tests para módulo de facturación electrónica
 * 
 * @author Tu Nombre <tu@email.com>
 */
class FacturacionElectronicaTest extends TestCase
{
    /**
     * @test
     */
    public function puede_generar_factura_electronica(): void
    {
        // Arrange
        $datos = [...];
        
        // Act
        $response = $this->postJson('/api/facturas', $datos);
        
        // Assert
        $response->assertStatus(201);
        $this->assertDatabaseHas('facturas', [...]);
    }
}
```

## 📝 Documentación

Si tu cambio afecta la API, actualiza:

1. **API_DOCUMENTATION.md** - Documentación de endpoints
2. **README.md** - Si cambia instalación o configuración
3. **MODELS_RELATIONS.md** - Si agregas nuevos modelos
4. **Inline docs** - Comentarios PHPDoc

## 🐛 Reportar Bugs

Para reportar bugs:

1. **GitHub Issues**: [Crear issue](https://github.com/SistemasUrsol/Ursol-CAST-API/issues)
2. **Email**: sistemas@ursol.com
3. **WhatsApp**: +506 8868-7765

**Template de Bug Report:**

```markdown
## Descripción del Bug
[Descripción clara del bug]

## Pasos para Reproducir
1. Ir a '...'
2. Hacer click en '...'
3. Ver error

## Comportamiento Esperado
[Qué debería pasar]

## Comportamiento Actual
[Qué pasa realmente]

## Screenshots
[Si aplica]

## Entorno
- OS: [ej. Ubuntu 22.04]
- PHP: [ej. 8.2.1]
- Laravel: [ej. 11.0]
- Navegador: [ej. Chrome 120]
```

## 💡 Solicitar Features

Para nuevas funcionalidades:

1. Crear issue con label "enhancement"
2. Describir el caso de uso
3. Esperar feedback del equipo

## 🎨 Estándares de Diseño

### Base de Datos

- **Tablas**: plural, snake_case (ej: `ordenes_compra`)
- **Timestamps**: `creado_en`, `actualizado_en`
- **Soft Deletes**: `eliminado` (boolean)
- **Estado**: `activo` (boolean)

### API

- **Rutas**: plural, kebab-case (ej: `/api/ordenes-compra`)
- **Responses**: JSON estructurado
- **Errores**: HTTP status codes apropiados

### Código

- **Clases**: PascalCase (ej: `FacturaElectronica`)
- **Métodos**: camelCase (ej: `generarComprobante()`)
- **Variables**: camelCase (ej: `$numeroFactura`)

## 🔒 Seguridad

Si encuentras una vulnerabilidad de seguridad:

**NO CREAR ISSUE PÚBLICO**

Contactar directamente a:
- Email: deadmooncr@gmail.com
- Email corporativo: sistemas@ursol.com
- WhatsApp: +506 8868-7765

## 📞 Contacto

**Sistemas Ursol S.A.**
- **Email**: sistemas@ursol.com
- **Email Técnico**: deadmooncr@gmail.com
- **WhatsApp**: +506 8868-7765
- **Web**: [ursol.com](https://ursol.com) | [ursol.net](https://ursol.net)
- **Repositorio**: [Ursol Reposit for Developers](https://sites.google.com/view/repdevursol/home/repositorio)
- **GitHub**: [github.com/SistemasUrsol](https://github.com/orgs/SistemasUrsol)

## 🙏 Agradecimientos

Agradecemos a todos los contribuidores que ayudan a mejorar este proyecto.

---

**Desarrollado con ❤️ y el "Toque Humano" por Sistemas Ursol S.A.**  
*Costa Rica | 30 años de experiencia tecnológica*

© 2025 Sistemas Ursol S.A. - Todos los derechos reservados
