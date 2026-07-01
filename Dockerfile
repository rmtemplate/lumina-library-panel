# syntax=docker/dockerfile:1

##############################################################################
# Stage 1 — Composer dependencies
#
# Resolved separately from the app source so Docker can cache this layer
# across builds whenever only application code changes (composer.lock
# unchanged -> no re-download, no re-resolve).
##############################################################################
FROM composer:2 AS vendor

WORKDIR /app

COPY composer.json composer.lock ./

# --ignore-platform-reqs: the `composer:2` image doesn't carry gd/intl/redis
# etc., so transitive ext-* / php-version checks would fail here. The real
# runtime image (php:8.3-fpm below) DOES have every required extension, so we
# resolve/download in this stage and let the runtime validate at actual use.
RUN composer install \
    --no-dev \
    --no-interaction \
    --no-autoloader \
    --no-scripts \
    --prefer-dist \
    --ignore-platform-reqs

COPY . .

RUN composer dump-autoload --optimize --no-dev --classmap-authoritative --ignore-platform-reqs \
    && composer run-script post-autoload-dump --no-interaction || true

##############################################################################
# Stage 2 — Frontend assets (Vite)
#
# Only the admin Blade panel (resources/views/admin/*, layouts/*) uses
# these — the mobile app talks to the JSON API directly. Built in its own
# stage so the final runtime image never carries Node or node_modules.
##############################################################################
FROM node:20-alpine AS frontend

WORKDIR /app

COPY package.json package-lock.json ./
RUN npm ci

COPY . .
RUN npm run build

##############################################################################
# Stage 3 — Runtime (PHP-FPM)
##############################################################################
FROM php:8.3-fpm-alpine AS runtime

LABEL org.opencontainers.image.title="lumina-library-app" \
      org.opencontainers.image.description="Lumina Library — Laravel backend (PHP-FPM runtime)"

# ── System packages ─────────────────────────────────────────────────────────
# - libpng/libjpeg-turbo/freetype: GD image backends (avatar/book cover/QR)
# - icu: intl extension
# - libzip: zip extension
# - fcgi: gives us cgi-fcgi for the container healthcheck (FastCGI ping)
# - mysql-client: only `mysqladmin`/`mysql` CLI, used by backup scripts run
#   via `docker compose exec`, not by the app itself
# - supervisor: manages queue/scheduler/reverb processes (see
#   docker/supervisor/*.conf) — this same image is reused for those roles,
#   selected via $CONTAINER_ROLE (see docker/php/entrypoint.sh)
RUN apk add --no-cache \
        bash \
        curl \
        fcgi \
        freetype \
        icu-libs \
        libjpeg-turbo \
        libpng \
        libzip \
        libwebp \
        mysql-client \
        netcat-openbsd \
        supervisor \
        tzdata \
    && apk add --no-cache --virtual .build-deps \
        $PHPIZE_DEPS \
        freetype-dev \
        icu-dev \
        libjpeg-turbo-dev \
        libpng-dev \
        libwebp-dev \
        libzip-dev \
    && docker-php-ext-configure gd \
        --with-freetype \
        --with-jpeg \
        --with-webp \
    && docker-php-ext-install -j"$(nproc)" \
        bcmath \
        exif \
        gd \
        intl \
        opcache \
        pcntl \
        pdo_mysql \
        zip \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del .build-deps \
    && rm -rf /tmp/pear /var/cache/apk/*

WORKDIR /var/www/html

# ── Application code ─────────────────────────────────────────────────────────
COPY --chown=www-data:www-data . .
COPY --from=vendor --chown=www-data:www-data /app/vendor ./vendor
COPY --from=frontend --chown=www-data:www-data /app/public/build ./public/build

# ── PHP / OPcache / FPM / Supervisor configuration ───────────────────────────
COPY docker/php/conf.d/zz-app.ini /usr/local/etc/php/conf.d/zz-app.ini
COPY docker/php/php-fpm.d/www.conf /usr/local/etc/php-fpm.d/www.conf
COPY docker/php/entrypoint.sh /usr/local/bin/entrypoint.sh
COPY docker/supervisor/queue.conf /etc/supervisor/queue.conf
COPY docker/supervisor/reverb.conf /etc/supervisor/reverb.conf
COPY docker/supervisor/scheduler.conf /etc/supervisor/scheduler.conf

RUN chmod +x /usr/local/bin/entrypoint.sh \
    && mkdir -p storage/framework/{cache,sessions,testing,views} storage/logs bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

# Intentionally stays root here: php-fpm's master process needs root to
# bind/fork and then drop each worker to www-data per php-fpm.d/www.conf,
# and supervisord needs root to setuid each managed program to www-data per
# its own `user=` directive (see docker/supervisor/*.conf). Neither
# php-fpm's workers nor any supervised program actually run as root —
# only the two parent/manager processes do, which is the standard,
# correct privilege-separation pattern for both.
EXPOSE 9000

HEALTHCHECK --interval=30s --timeout=5s --start-period=20s --retries=3 \
    CMD SCRIPT_NAME=/ping SCRIPT_FILENAME=/ping REQUEST_METHOD=GET \
        cgi-fcgi -bind -connect 127.0.0.1:9000 || exit 1

ENTRYPOINT ["entrypoint.sh"]
CMD ["php-fpm"]
