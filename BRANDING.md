# 🎨 Guía de Identidad Visual - Ursol CAST API

<p align="center">
  <img src="./public/assets/logos/ursol-cast-api-logo.png" width="600" alt="Ursol CAST API Logo">
</p>

<p align="center">
  <img src="./public/assets/logos/ursol-icon.webp" width="100" alt="Sistemas Ursol Icon">
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
| **Logo CAST API** | `/public/assets/logos/ursol-cast-api-logo.png` | 2000x800px | PNG | Logo principal del proyecto |
| **Icono Ursol** | `/public/assets/logos/ursol-icon.webp` | 512x512px | WebP | Icono de la empresa desarrolladora |

---

## 🏷️ Logos Oficiales

### 1. Logo Ursol CAST API

**Archivo:** `ursol-cast-api-logo.png`

<p align="center">
  <img src="./public/assets/logos/ursol-cast-api-logo.png" width="600" alt="Ursol CAST API Logo">
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

### 2. Icono Sistemas Ursol S.A.

**Archivo:** `ursol-icon.webp`

<p align="center">
  <img src="./public/assets/logos/ursol-icon.webp" width="150" alt="Sistemas Ursol Icon">
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
- **Icono Ursol:** Mantener un margen mínimo equivalente al 5% del tamaño del icono

### Tamaños Mínimos

- **Logo CAST API:** 
  - Web: 300px de ancho mínimo
  - Impresión: 5cm de ancho mínimo
  
- **Icono Ursol:**
  - Web: 32px de lado mínimo (favicon)
  - Impresión: 1cm de lado mínimo

### Fondos Permitidos

- **Logo CAST API:**
  - ✅ Fondo blanco o claro
  - ✅ Fondo con transparencia
  - ⚠️ Evitar fondos oscuros que reduzcan contraste
  
- **Icono Ursol:**
  - ✅ Cualquier fondo (diseñado para versatilidad)
  - ✅ Con o sin transparencia

---

## 🎨 Colores Corporativos

### Paleta Principal - Sistemas Ursol S.A.

```css
/* Azul Corporativo */
--ursol-blue: #0066CC;
--ursol-blue-dark: #004C99;
--ursol-blue-light: #3385D6;

/* Gris Corporativo */
--ursol-gray: #4A4A4A;
--ursol-gray-light: #8A8A8A;
--ursol-gray-lighter: #E5E5E5;

/* Acentos */
--ursol-accent: #FF6B00;
--ursol-success: #28A745;
--ursol-warning: #FFC107;
--ursol-danger: #DC3545;
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
  <img src="./public/assets/logos/ursol-cast-api-logo.png" width="600" alt="Ursol CAST API Logo">
</p>

<p align="center">
  <img src="./public/assets/logos/ursol-icon.webp" width="80" alt="Sistemas Ursol Icon">
</p>
```

### Archivos de Documentación Secundarios

```markdown
---
**Desarrollado por:**

<img src="./public/assets/logos/ursol-icon.webp" width="40" alt="Ursol Icon"> **Sistemas Ursol S.A.**
---
```

### Firma de Commits

```
feat: Implementar nueva funcionalidad

Desarrollado por: Sistemas Ursol S.A.
Proyecto: Ursol CAST API
```

---

## 🌐 Uso en HTML/Web

### Favicon

```html
<!-- public/index.html -->
<link rel="icon" type="image/webp" href="/assets/logos/ursol-icon.webp">
<link rel="apple-touch-icon" href="/assets/logos/ursol-icon.webp">
```

### Open Graph / Social Media

```html
<meta property="og:image" content="/assets/logos/ursol-cast-api-logo.png">
<meta property="og:image:width" content="2000">
<meta property="og:image:height" content="800">
<meta name="twitter:image" content="/assets/logos/ursol-cast-api-logo.png">
```

### Email Signatures

```html
<img src="https://tu-dominio.com/assets/logos/ursol-icon.webp" 
     width="64" 
     alt="Sistemas Ursol" 
     style="vertical-align:middle; margin-right:10px;">
<strong>Sistemas Ursol S.A.</strong>
```

---

## 📱 Adaptaciones para Diferentes Medios

### Documentación Técnica
- **Logo CAST API:** 400-600px de ancho
- **Icono Ursol:** 60-100px de lado

### Presentaciones
- **Logo CAST API:** Diapositiva de portada (ancho completo)
- **Icono Ursol:** Esquina superior derecha (80-120px)

### Redes Sociales
- **LinkedIn/Facebook:** Logo CAST API 1200x628px
- **Twitter:** Logo CAST API 1200x675px
- **Avatar/Perfil:** Icono Ursol 512x512px

### Firma de Email
- **Icono Ursol:** 40-64px de lado
- Acompañado de texto corporativo

---

## ⚠️ Usos NO Permitidos

❌ **No está permitido:**
- Distorsionar las proporciones de los logos
- Cambiar los colores originales
- Agregar efectos (sombras, brillos, gradientes no autorizados)
- Rotar o inclinar los logos
- Usar en contextos que no representen los valores de Ursol S.A.
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

Para consultas sobre el uso de la marca **Sistemas Ursol S.A.** o **Ursol CAST API**:

- **Empresa:** Sistemas Ursol S.A.
- **Fundador:** Eduardo Alberto Ureña Solano
- **Equipo de Desarrollo:** Jeremy Arias Solano
- **WhatsApp:** [+506 8868-7765](https://wa.me/50688687765)
- **Web:** [ursol.com](https://ursol.com) | [ursol.net](https://ursol.net)
- **GitHub:** [SistemasUrsol](https://github.com/SistemasUrsol)

---

## 📜 Derechos de Autor

© 2024-2025 **Sistemas Ursol S.A.** - Todos los derechos reservados.

Los logos y marcas son propiedad de **Sistemas Ursol S.A.** El código fuente del proyecto está licenciado bajo **MIT License**, pero las marcas y logos mantienen derechos reservados.

---

<p align="center">
  <strong>Desarrollado con ❤️ y el "Toque Humano" por Sistemas Ursol S.A.</strong><br>
  <em>San José, Costa Rica 🇨🇷 | Desde 1995</em>
</p>
