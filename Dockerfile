# Dockerfile para Ursol CAST API
# Multi-stage build para optimización

# Etapa 1: Dependencias de Composer
FROM php:8.4-alpine AS composer

# Install Composer
COPY --from=composer/composer:2-bin /composer /usr/bin/composer

# Install unzip explicitly needed for composer
RUN apk add --no-cache unzip

WORKDIR /app

# Copiar solo archivos necesarios para composer
COPY composer.json composer.lock ./

# Instalar dependencias (sin dev en producción)
RUN composer install \
    --no-dev \
    --no-scripts \
    --no-autoloader \
    --prefer-dist \
    --optimize-autoloader

# Copiar todo el código
COPY . .

# Generar autoloader optimizado
RUN composer dump-autoload --optimize --classmap-authoritative

# Etapa 2: Imagen final PHP-FPM
FROM php:8.4-fpm-alpine

# Metadata
LABEL maintainer="Sistemas Ursol S.A. <sistemas@ursol.com>"
LABEL version="1.0.0"
LABEL description="Ursol CAST API - ERP System"

# Instalar dependencias del sistema
RUN apk add --no-cache \
    bash \
    curl \
    freetype-dev \
    git \
    icu-dev \
    jpeg-dev \
    libpng-dev \
    libzip-dev \
    mysql-client \
    oniguruma-dev \
    postgresql-dev \
    zip \
    unzip \
    linux-headers

# Instalar extensiones de PHP
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
    bcmath \
    exif \
    gd \
    intl \
    mbstring \
    pdo \
    pdo_mysql \
    pdo_pgsql \
    pcntl \
    zip

# Instalar Redis extension
RUN apk add --no-cache --virtual .build-deps $PHPIZE_DEPS \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del .build-deps

# XDebug solo para desarrollo (no incluir en producción)
ARG INSTALL_XDEBUG=false
RUN if [ "$INSTALL_XDEBUG" = "true" ]; then \
    apk add --no-cache --virtual .build-deps $PHPIZE_DEPS \
    && pecl install xdebug \
    && docker-php-ext-enable xdebug \
    && apk del .build-deps; \
    fi

# Configuración de PHP personalizada
COPY docker/php/php.ini /usr/local/etc/php/conf.d/custom.ini
COPY docker/php/php-fpm.conf /usr/local/etc/php-fpm.d/www.conf
COPY docker/php/xdebug.ini /tmp/xdebug.ini

# XDebug config solo si se instaló
RUN if [ "$INSTALL_XDEBUG" = "true" ]; then \
    cp /tmp/xdebug.ini /usr/local/etc/php/conf.d/xdebug.ini; \
    fi \
    && rm -f /tmp/xdebug.ini

# Crear usuario para Laravel
RUN addgroup -g 1000 laravel && \
    adduser -u 1000 -G laravel -s /bin/bash -D laravel

# Establecer directorio de trabajo
WORKDIR /var/www/html

# Copiar código desde etapa de composer
COPY --from=composer --chown=laravel:laravel /app /var/www/html

# Crear directorios necesarios y establecer permisos
RUN mkdir -p \
    storage/app/public \
    storage/framework/cache \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache \
    && chown -R laravel:laravel /var/www/html \
    && chmod -R 775 storage bootstrap/cache

# Cambiar a usuario laravel
USER laravel

# Exponer puerto PHP-FPM
EXPOSE 9000

# Health check
HEALTHCHECK --interval=30s --timeout=3s --start-period=40s \
    CMD php-fpm -t || exit 1

# Comando por defecto
CMD ["php-fpm"]
