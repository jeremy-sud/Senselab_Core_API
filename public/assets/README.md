# 📁 Assets - Senselab Core API

Esta carpeta contiene todos los recursos estáticos (imágenes, logos, íconos) utilizados en el proyecto.

## 📂 Estructura de Carpetas

```
assets/
├── logos/           # Logos y marca corporativa
│   ├── senselab-core-api-logo.png    # Logo principal del proyecto (2000x800px)
│   └── senselab-icon.png            # Icono Senselab (512x512px)
├── images/          # Imágenes generales del proyecto
└── README.md        # Este archivo
```

## 🏷️ Logos Disponibles

### 1. Logo Senselab Core API
- **Archivo:** `logos/senselab-core-api-logo.png`
- **Dimensiones:** 2000 x 800 píxeles
- **Formato:** PNG con transparencia
- **Peso:** ~643 KB
- **Uso:** Logo principal del proyecto en documentación, README, presentaciones

### 2. Icono Senselab
- **Archivo:** `logos/senselab-icon.png`
- **Dimensiones:** 512 x 512 píxeles
- **Formato:** WebP optimizado
- **Peso:** ~2.9 KB
- **Uso:** Icono corporativo, favicon, avatares, redes sociales

## 📖 Guía de Uso

Para información detallada sobre el uso correcto de los logos y la identidad visual, consulta:

**[📄 BRANDING.md](../../BRANDING.md)**

## 🔗 Referencias en Código

### En Markdown (README, documentación)

```markdown
<!-- Logo principal -->
<img src="./public/assets/logos/senselab-core-api-logo.png" width="600" alt="Senselab Core API Logo">

<!-- Icono corporativo -->
<img src="./public/assets/logos/senselab-icon.png" width="80" alt="Senselab Icon">
```

### En HTML (vistas, emails)

```html
<!-- Logo principal -->
<img src="/assets/logos/senselab-core-api-logo.png" 
     alt="Senselab Core API Logo" 
     style="max-width: 600px; height: auto;">

<!-- Icono corporativo -->
<img src="/assets/logos/senselab-icon.png" 
     alt="Senselab Icon" 
     width="80" 
     height="80">
```

### En Laravel Blade

```blade
<!-- Logo principal -->
<img src="{{ asset('assets/logos/senselab-core-api-logo.png') }}" 
     alt="Senselab Core API Logo" 
     class="img-fluid">

<!-- Icono corporativo -->
<img src="{{ asset('assets/logos/senselab-icon.png') }}" 
     alt="Senselab Icon" 
     width="80">
```

## 🎨 Optimización de Imágenes

Los assets en esta carpeta están optimizados para web:

- **PNG:** Comprimido con pérdida mínima de calidad
- **WebP:** Formato moderno con mejor compresión (80% menos peso que PNG)
- **Iconos:** Tamaños optimizados para diferentes contextos

### Recomendaciones:
- ✅ Usar WebP cuando sea posible (mejor rendimiento)
- ✅ Incluir fallback PNG para compatibilidad
- ✅ Lazy loading para imágenes grandes
- ✅ Responsive images con `srcset`

## 📝 Agregar Nuevas Imágenes

Si necesitas agregar nuevas imágenes al proyecto:

1. **Optimiza** la imagen antes de agregarla
2. **Nombra** el archivo de forma descriptiva (kebab-case)
3. **Ubica** en la carpeta correspondiente:
   - `logos/` - Solo logos y marca corporativa
   - `images/` - Imágenes generales del proyecto
4. **Documenta** su uso en este README si es relevante

### Herramientas de Optimización:

```bash
# Optimizar PNG
pngquant *.png --quality=65-80 --ext=.png --force

# Convertir a WebP
cwebp -q 80 input.png -o output.webp

# ImageMagick (redimensionar)
convert input.png -resize 512x512 output.png
```

## 🚫 Archivos NO Incluidos

Esta carpeta **NO** debe contener:
- ❌ Archivos temporales o de caché
- ❌ Imágenes de usuarios (van en `storage/`)
- ❌ Archivos fuente editables (.psd, .ai, .sketch)
- ❌ Versiones sin optimizar de las imágenes
- ❌ Archivos de más de 1 MB sin justificación

## 📜 Licencia de Assets

Los logos y marca **Senselab** y **Senselab Core API** son propiedad de:

**Senselab**  
San José, Costa Rica 🇨🇷

Todos los derechos reservados © 2024-2025

El código del proyecto está bajo **MIT License**, pero los assets gráficos mantienen derechos reservados.

---

<p align="center">
  <img src="./logos/senselab-icon.png" width="60" alt="Senselab Icon"><br>
  <strong>Senselab</strong><br>
  <em>El "Toque Humano" en Tecnología</em>
</p>
