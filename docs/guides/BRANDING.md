# 🎨 Guía de Identidad Visual - Senselab Core API

<p align="center">
  <img src="./public/assets/logos/senselab-core-api-logo.png" width="600" alt="Senselab Core API Logo">
</p>

<p align="center">
  <img src="./public/assets/logos/senselab-icon.png" width="100" alt="Senselab Icon">
</p>

---

## 📋 Tabla de Contenidos

- [Assets Disponibles](#-assets-disponibles)
- [Logos Oficiales](#-logos-oficiales)
- [Guías de Uso](#-guías-de-uso)
- [Colores Corporativos](#-colores-corporativos)
- [Tipografías](#-tipografías)
- [Uso en Documentación](#-uso-en-documentación)

---

## 📦 Assets Disponibles

Todos los assets de marca están ubicados en la carpeta `/public/assets/logos/`:

### Logos Principales

| Asset | Ubicación | Dimensiones | Formato | Uso |
|-------|-----------|-------------|---------|-----|
| **Logo CAST API** | `/public/assets/logos/senselab-core-api-logo.png` | 2000x800px | PNG | Logo principal del proyecto |
| **Icono Senselab** | `/public/assets/logos/senselab-icon.png` | 512x512px | WebP | Icono de la empresa desarrolladora |

---

## 🏷️ Logos Oficiales

### 1. Logo Senselab Core API

**Archivo:** `senselab-core-api-logo.png`

<p align="center">
  <img src="./public/assets/logos/senselab-core-api-logo.png" width="600" alt="Senselab Core API Logo">
</p>

**Características:**
- **Formato:** PNG con transparencia
- **Dimensiones:** 2000 x 800 píxeles
- **Peso:** ~643 KB
- **Uso:** Logo principal del proyecto API
- **Contextos:** README, documentación, presentaciones, landing pages

**Recomendaciones:**
- Ancho recomendado en web: 400-600px
- Mantener proporciones originales (2.5:1)
- Fondo claro para mejor visibilidad
- No distorsionar ni modificar colores

---

### 2. Icono Senselab

**Archivo:** `senselab-icon.png`

<p align="center">
  <img src="./public/assets/logos/senselab-icon.png" width="150" alt="Senselab Icon">
</p>

**Características:**
- **Formato:** WebP (optimizado para web)
- **Dimensiones:** 512 x 512 píxeles
- **Peso:** ~2.9 KB
- **Uso:** Icono corporativo, favicon, redes sociales
- **Contextos:** Avatares, firma de correos, badges, notificaciones

**Recomendaciones:**
- Tamaño recomendado en web: 64-150px
- Ideal para espacios cuadrados
- Fondo transparente
- Versátil para diferentes contextos

---

## 📐 Guías de Uso

### Espaciado y Área de Seguridad

- **Logo CAST API:** Mantener un margen mínimo equivalente al 10% de la altura del logo
- **Icono Senselab:** Mantener un margen mínimo equivalente al 5% del tamaño del icono

### Tamaños Mínimos

- **Logo CAST API:** 
  - Web: 300px de ancho mínimo
  - Impresión: 5cm de ancho mínimo
  
- **Icono Senselab:**
  - Web: 32px de lado mínimo (favicon)
  - Impresión: 1cm de lado mínimo

### Fondos Permitidos

- **Logo CAST API:**
  - ✅ Fondo blanco o claro
  - ✅ Fondo con transparencia
  - ⚠️ Evitar fondos oscuros que reduzcan contraste
  
- **Icono Senselab:**
  - ✅ Cualquier fondo (diseñado para versatilidad)
  - ✅ Con o sin transparencia

---

## 🎨 Colores Corporativos

### Paleta Principal - Senselab

```css
/* Azul Corporativo */
--senselab-blue: #0066CC;
--senselab-blue-dark: #004C99;
--senselab-blue-light: #3385D6;

/* Gris Corporativo */
--senselab-gray: #4A4A4A;
--senselab-gray-light: #8A8A8A;
--senselab-gray-lighter: #E5E5E5;

/* Acentos */
--senselab-accent: #FF6B00;
--senselab-success: #28A745;
--senselab-warning: #FFC107;
--senselab-danger: #DC3545;
```

### Paleta Secundaria - CAST API

```css
/* Tecnología */
--cast-primary: #2563EB;
--cast-secondary: #7C3AED;
--cast-accent: #10B981;

/* Neutros */
--cast-dark: #1F2937;
--cast-medium: #6B7280;
--cast-light: #F3F4F6;
```

---

## 🔤 Tipografías

### Familia Principal

**Inter / San Francisco / Segoe UI**
- Encabezados: 600-700 weight
- Cuerpo: 400 weight
- Código: Monospace (Fira Code, JetBrains Mono)

### Uso en Documentación

```markdown
# Título Principal (H1) - Inter Bold
## Subtítulo (H2) - Inter SemiBold
### Sección (H3) - Inter SemiBold
**Énfasis** - Inter Bold
*Itálica* - Inter Regular Italic
`Código` - Fira Code
```

---

## 📄 Uso en Documentación

### README.md

```markdown
<p align="center">
  <img src="./public/assets/logos/senselab-core-api-logo.png" width="600" alt="Senselab Core API Logo">
</p>

<p align="center">
  <img src="./public/assets/logos/senselab-icon.png" width="80" alt="Senselab Icon">
</p>
```

### Archivos de Documentación Secundarios

```markdown
---
**Desarrollado por:**

<img src="./public/assets/logos/senselab-icon.png" width="40" alt="Senselab Icon"> **Senselab**
---
```

### Firma de Commits

```
feat: Implementar nueva funcionalidad

Desarrollado por: Senselab
Proyecto: Senselab Core API
```

---

## 🌐 Uso en HTML/Web

### Favicon

```html
<!-- public/index.html -->
<link rel="icon" type="image/png" href="/assets/logos/senselab-icon.png">
<link rel="apple-touch-icon" href="/assets/logos/senselab-icon.png">
```

### Open Graph / Social Media

```html
<meta property="og:image" content="/assets/logos/senselab-core-api-logo.png">
<meta property="og:image:width" content="2000">
<meta property="og:image:height" content="800">
<meta name="twitter:image" content="/assets/logos/senselab-core-api-logo.png">
```

### Email Signatures

```html
<img src="https://tu-dominio.com/assets/logos/senselab-icon.png" 
     width="64" 
     alt="Senselab" 
     style="vertical-align:middle; margin-right:10px;">
<strong>Senselab</strong>
```

---

## 📱 Adaptaciones para Diferentes Medios

### Documentación Técnica
- **Logo CAST API:** 400-600px de ancho
- **Icono Senselab:** 60-100px de lado

### Presentaciones
- **Logo CAST API:** Diapositiva de portada (ancho completo)
- **Icono Senselab:** Esquina superior derecha (80-120px)

### Redes Sociales
- **LinkedIn/Facebook:** Logo CAST API 1200x628px
- **Twitter:** Logo CAST API 1200x675px
- **Avatar/Perfil:** Icono Senselab 512x512px

### Firma de Email
- **Icono Senselab:** 40-64px de lado
- Acompañado de texto corporativo

---

## ⚠️ Usos NO Permitidos

❌ **No está permitido:**
- Distorsionar las proporciones de los logos
- Cambiar los colores originales
- Agregar efectos (sombras, brillos, gradientes no autorizados)
- Rotar o inclinar los logos
- Usar en contextos que no representen los valores de Senselab S.A.
- Modificar o recrear el logo sin autorización
- Usar versiones de baja calidad o pixeladas

✅ **Está permitido:**
- Escalar proporcionalmente
- Usar en fondo blanco o transparente
- Incluir en documentación del proyecto
- Usar en comunicaciones oficiales relacionadas al proyecto
- Compartir en presentaciones técnicas

---

## 📞 Contacto para Uso de Marca

Para consultas sobre el uso de la marca **Senselab** o **Senselab Core API**:

- **Empresa:** Senselab
- **Fundador:** Senselab Team
- **Equipo de Desarrollo:** Jeremy Arias Solano
- **WhatsApp:** [+(506)8973-5665](https://wa.me/50689735665)
- **Web:** [senselab.com](https://senselab.com) | [senselab.com](https://senselab.com)
- **GitHub:** [SenseLab-dev](https://github.com/SenseLab-dev)

---

## 📜 Derechos de Autor

© 2024-2025 **Senselab** - Todos los derechos reservados.

Los logos y marcas son propiedad de **Senselab** El código fuente del proyecto está licenciado bajo **MIT License**, pero las marcas y logos mantienen derechos reservados.

---

<p align="center">
  <strong>"No hacemos cualquier cosa. Hacemos cosas con sentido."</strong><br>
  <em>San José, Costa Rica 🇨🇷 | Desde 1995</em>
</p>
