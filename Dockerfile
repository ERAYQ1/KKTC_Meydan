FROM php:8.2-fpm-alpine

# Install system dependencies & PHP extensions required by KKTC Meydan Platformu
RUN apk add --no-cache \
    bash \
    curl \
    git \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    libzip-dev \
    icu-dev \
    oniguruma-dev \
    mariadb-client \
    nodejs \
    npm \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo_mysql \
        gd \
        zip \
        intl \
        mbstring \
        bcmath \
        opcache

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Hide PHP version fingerprint
RUN echo "expose_php = Off" > /usr/local/etc/php/conf.d/expose-php-off.ini

# Raise upload/memory limits for avatar & attachment uploads (defaults are too low for photos)
RUN { \
        echo "upload_max_filesize = 20M"; \
        echo "post_max_size = 25M"; \
        echo "memory_limit = 256M"; \
    } > /usr/local/etc/php/conf.d/zz-kktc-meydan-limits.ini

# Set working directory
WORKDIR /var/www/html

# Permissions (build-time baseline; storage/ is re-asserted at container
# start by entrypoint.sh since the bind-mounted volume overrides this)
RUN chown -R www-data:www-data /var/www/html

COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 9000
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["php-fpm"]
