# ============================================================
# Stage 1: Node 18 – compile frontend assets
# node-sass@8 supports Node 14–18; do not upgrade to Node 20+
# ============================================================
FROM node:18-bookworm-slim AS assets

# node-sass may need to compile native bindings as a fallback
RUN apt-get -o Acquire::Check-Valid-Until=false update && apt-get install -y python3 make g++ \
    && rm -rf /var/lib/apt/lists/*
WORKDIR /build

COPY package.json yarn.lock ./
RUN yarn install --frozen-lockfile --non-interactive

COPY webpack.config.js ./
COPY assets/ ./assets/

RUN yarn build

# ============================================================
# Stage 2: PHP 8.0-FPM – production application
# ============================================================
FROM php:8.0-fpm-bullseye AS app

RUN sed -i 's|http://deb.debian.org/debian |http://archive.debian.org/debian |g' /etc/apt/sources.list \
    && sed -i '/deb.debian.org\/debian-security/d' /etc/apt/sources.list

RUN apt-get -o Acquire::Check-Valid-Until=false update && apt-get install -y \
        libicu-dev \
        libzip-dev \
        unzip \
        git \
        gosu \
    && docker-php-ext-configure intl \
    && docker-php-ext-install pdo_mysql intl opcache zip \
    && rm -rf /var/lib/apt/lists/*

# OPcache tuned for immutable production code
RUN { \
    echo 'opcache.enable=1'; \
    echo 'opcache.memory_consumption=256'; \
    echo 'opcache.max_accelerated_files=20000'; \
    echo 'opcache.validate_timestamps=0'; \
    echo 'opcache.revalidate_freq=0'; \
} > /usr/local/etc/php/conf.d/opcache-prod.ini

COPY --from=composer:2.2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Install PHP deps before copying source for better layer caching.
# vendor/ is excluded from the build context via .dockerignore.
COPY composer.json composer.lock symfony.lock ./
RUN composer install \
    --no-dev \
    --no-interaction \
    --no-progress \
    --optimize-autoloader \
    --no-scripts \
    --no-cache

# Copy application source (vendor/, var/, public/build/, node_modules/ excluded)
COPY . .

# Overlay webpack-built frontend assets
COPY --from=assets /build/public/build/ ./public/build/

# Install bundle web assets (public/bundles/*).
# APP_SECRET and DATABASE_URL are placeholders — only needed for console boot.
# cache:clear is intentionally skipped here; it runs at container start with real env.
RUN APP_ENV=prod \
    APP_SECRET=build-placeholder \
    DATABASE_URL=mysql://x:x@localhost/x \
    php bin/console assets:install public --no-interaction 2>/dev/null || true

RUN mkdir -p public/img && cp -r assets/img/* public/img/

# Snapshot of public/ so the entrypoint can sync it to the shared volume on every deploy.
RUN cp -r public/ /public-src/

RUN mkdir -p var/cache var/log \
    && chown -R www-data:www-data var/ /public-src/ \
    && chmod -R 775 var/

# Pass container env vars through to php-fpm workers (default is clear_env=yes)
COPY docker/php/zz-docker.conf /usr/local/etc/php-fpm.d/zz-docker.conf

COPY docker/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

ENTRYPOINT ["/entrypoint.sh"]
CMD ["php-fpm", "-F"]

EXPOSE 9000
