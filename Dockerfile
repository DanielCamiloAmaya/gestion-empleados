FROM node:22-alpine AS assets
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci
COPY resources ./resources
COPY vite.config.js ./
RUN npm run build

FROM composer:2 AS vendors
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --no-progress --prefer-dist --optimize-autoloader --no-scripts

FROM php:8.3-fpm-alpine AS runtime
RUN apk add --no-cache icu-libs libpq libzip nginx supervisor \
    && apk add --no-cache --virtual .build-deps icu-dev libpq-dev libzip-dev $PHPIZE_DEPS \
    && docker-php-ext-install -j"$(nproc)" bcmath intl opcache pcntl pdo_pgsql zip \
    && apk del .build-deps

WORKDIR /var/www/html
COPY . .
COPY --from=vendors /app/vendor ./vendor
COPY --from=assets /app/public/build ./public/build
COPY infra/docker/php.ini /usr/local/etc/php/conf.d/peopleos.ini
COPY infra/docker/nginx.conf /etc/nginx/nginx.conf
COPY infra/docker/supervisord.conf /etc/supervisord.conf
COPY infra/docker/entrypoint.sh /usr/local/bin/peopleos-entrypoint

RUN chmod +x /usr/local/bin/peopleos-entrypoint \
    && mkdir -p /tmp/nginx/client_temp /tmp/nginx/proxy_temp /tmp/nginx/fastcgi_temp \
    && chown -R www-data:www-data storage bootstrap/cache /tmp/nginx

USER www-data
EXPOSE 8080
ENTRYPOINT ["peopleos-entrypoint"]
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisord.conf"]
