FROM php:8.3-fpm-alpine

ENV COMPOSER_HOME=/tmp/composer \
    TZ=UTC

RUN set -eux; \
    apk add --no-cache --update \
        bash git curl unzip icu-dev oniguruma-dev libzip-dev postgresql-dev $PHPIZE_DEPS; \
    docker-php-ext-configure intl; \
    docker-php-ext-install -j"$(nproc)" intl pdo pdo_pgsql; \
    pecl install redis; \
    docker-php-ext-enable redis; \
    apk del --no-network $PHPIZE_DEPS; \
    rm -rf /var/cache/apk/* /tmp/* /var/tmp/*

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

WORKDIR /var/www/html

# PHP settings
COPY docker/php/php.ini /usr/local/etc/php/conf.d/zz-custom.ini

# Ensure www-data has UID/GID matching host optional
ARG PUID=1000
ARG PGID=1000
RUN addgroup -g ${PGID} appgroup || true; \
    adduser -D -G appgroup -u ${PUID} appuser || true; \
    chown -R appuser:appgroup /var/www/html

USER appuser

CMD ["php-fpm"]

