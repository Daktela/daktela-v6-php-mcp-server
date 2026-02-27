FROM php:8.2-fpm

RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libzip-dev \
    nginx \
    && docker-php-ext-install zip \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# nginx config
COPY docker/nginx.conf /etc/nginx/sites-available/default

# PHP-FPM config
COPY docker/php-fpm.conf /etc/php-fpm.conf

# Entrypoint
COPY docker/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

WORKDIR /app

EXPOSE 8080

ENTRYPOINT ["/entrypoint.sh"]
