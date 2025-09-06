# ---------- Base ----------
FROM php:8.3-fpm-alpine AS base
RUN apk add --no-cache bash git tini icu-dev libzip-dev oniguruma-dev \
    libpng-dev libjpeg-turbo-dev freetype-dev curl wget supervisor fcgi
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
 && docker-php-ext-install -j$(nproc) pdo_mysql bcmath intl zip gd exif opcache pcntl sockets

COPY docker/php/www.conf /usr/local/etc/php-fpm.d/www.conf
COPY docker/php/php.ini  /usr/local/etc/php/conf.d/zz-custom.ini
COPY docker/php/opcache.ini /usr/local/etc/php/conf.d/opcache.ini

ARG USER_ID=1000
ARG GROUP_ID=1000
RUN addgroup -g $GROUP_ID appuser \
 && adduser -D -G appuser -u $USER_ID appuser \
 && mkdir -p /var/www/html && chown -R appuser:appuser /var/www
WORKDIR /var/www/html

# Composer
FROM composer:2 AS composerdownloader

# ---------- local (dev) ----------
FROM base AS local
COPY --from=composerdownloader /usr/bin/composer /usr/bin/composer
ENV COMPOSER_ALLOW_SUPERUSER=1
USER appuser
ENTRYPOINT ["/sbin/tini","--"]

# ---------- deps (vendor cache) ----------
FROM base AS deps
COPY --from=composerdownloader /usr/bin/composer /usr/bin/composer
ENV COMPOSER_ALLOW_SUPERUSER=1
WORKDIR /var/www/html
COPY --chown=appuser:appuser composer.json composer.lock ./
RUN --mount=type=cache,target=/tmp/composer \
    composer install --no-dev --prefer-dist --no-interaction --no-progress --optimize-autoloader

# ---------- runtime (prod FPM) ----------
FROM base AS runtime
COPY --from=deps /var/www/html/vendor /var/www/html/vendor
COPY --chown=appuser:appuser . /var/www/html
USER appuser
ENTRYPOINT ["/sbin/tini","--"]
CMD ["php-fpm"]
EXPOSE 9000
HEALTHCHECK --interval=10s --timeout=2s --retries=10 \
  CMD cgi-fcgi -bind -connect 127.0.0.1:9000 -ping -path /ping || exit 1

# ---------- worker ----------
FROM runtime AS worker
USER root
COPY docker/worker/supervisord.conf /etc/supervisord.conf
COPY docker/worker/laravel-worker.ini /etc/supervisor.d/laravel-worker.ini
USER appuser
CMD ["supervisord","-c","/etc/supervisord.conf"]

# ---------- scheduler ----------
FROM runtime AS scheduler
USER root
COPY docker/scheduler/crontab /etc/crontabs/appuser
USER appuser
CMD ["crond","-f","-l","2"]