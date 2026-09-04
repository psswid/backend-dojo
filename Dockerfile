# Backend Dojo — PHP-FPM runtime (Laravel 13)
# Source is mounted as a volume; this image provides the PHP runtime + extensions.
FROM php:8.4-fpm-alpine

WORKDIR /var/www/html

# System dependencies for PHP extensions
RUN apk add --no-cache \
    bash \
    curl \
    git \
    unzip \
    libzip-dev \
    icu-dev \
    libpq-dev \
    oniguruma-dev \
    linux-headers \
    $PHPIZE_DEPS

# PHP extensions
RUN docker-php-ext-install -j"$(nproc)" \
    pdo_mysql \
    pdo_pgsql \
    bcmath \
    intl \
    zip \
    pcntl \
    opcache

# Composer (for convenience inside the container)
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Run php-fpm as the host user (uid 1000) so the mounted source — owned by the
# host user — is readable/writable without chmod hacks.
RUN addgroup -S -g 1000 dojo \
    && adduser -S -D -u 1000 -G dojo dojo \
    && sed -i 's/^user = www-data/user = dojo/; s/^group = www-data/group = dojo/' /usr/local/etc/php-fpm.d/www.conf

# PHP runtime tuning
COPY docker/php/php.ini /usr/local/etc/php/conf.d/zz-docker.ini

EXPOSE 9000

CMD ["php-fpm"]
