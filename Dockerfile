# Multi-stage Dockerfile (production-friendly)
# Stages: base -> vendor -> node-builder -> runtime

ARG PHP_VERSION=8.4
ARG NODE_VERSION=18

### Base image: install system deps and PHP extensions (shared)
FROM php:${PHP_VERSION}-fpm AS base

ENV DEBIAN_FRONTEND=noninteractive

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        ca-certificates \
        gnupg \
        wget \
        curl \
        git \
        unzip \
        zip \
        build-essential \
        pkg-config \
        libpq-dev \
        libzip-dev \
        libpng-dev \
        libjpeg62-turbo-dev \
        libonig-dev \
        libicu-dev \
        zlib1g-dev \
        libxml2-dev \
        libcurl4-openssl-dev \
        cron \
        supervisor \
    && docker-php-ext-install -j"$(nproc)" \
        pdo \
        pdo_pgsql \
        mbstring \
        xml \
        curl \
        zip \
        bcmath \
        pcntl \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www


### Vendor stage: install PHP dependencies (cached)
FROM base AS vendor

# Install composer and install only PHP dependencies (no scripts)
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
COPY composer.json composer.lock ./
RUN composer install \
    --no-dev --prefer-dist --no-interaction --no-progress --optimize-autoloader --no-scripts


### Node build stage: install node, npm and build front-end assets
FROM node:${NODE_VERSION} AS node-builder
WORKDIR /app
# Copy only package files to leverage cache
COPY package.json package-lock.json ./
RUN npm ci --silent
# Ensure a public folder exists even if the project doesn't output to it
RUN mkdir -p /app/public
# Run build if script exists (non-fatal)
RUN if grep -q "\"build\"" package.json; then npm run build || true; fi


### Runtime stage: copy vendor, built assets and application files
FROM base AS runtime

# Set timezone to Asia/Manila properly
ENV TZ=Asia/Manila
RUN ln -snf /usr/share/zoneinfo/Asia/Manila /etc/localtime \
    && echo "Asia/Manila" > /etc/timezone \
    && echo "date.timezone=Asia/Manila" > /usr/local/etc/php/conf.d/timezone.ini

# Copy PHP vendor from vendor stage
COPY --from=vendor /var/www/vendor /var/www/vendor

# Copy built assets (if build outputs to /app/public)
COPY --from=node-builder /app/public /var/www/public

# Copy application source
COPY . /var/www

# Ensure storage and cache are writable
RUN mkdir -p /var/www/storage /var/www/bootstrap/cache /var/www/storage/framework/views \
    && chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache /var/www/vendor || true \
    && chmod -R 775 /var/www/storage /var/www/bootstrap/cache || true

# Install composer in runtime to run post-install scripts that require app files
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer || true

# Optimize autoload and run package discovery (non-fatal to avoid build breaks)
RUN if command -v composer >/dev/null 2>&1; then composer dump-autoload --optimize --no-interaction || true; fi \
    && if [ -f artisan ]; then php artisan package:discover --ansi || true; fi

# Set up Laravel scheduler cron job with logging
RUN echo "* * * * * cd /var/www && /usr/local/bin/php artisan schedule:run >> /var/www/storage/logs/cron.log 2>&1" > /etc/cron.d/laravel-scheduler \
    && chmod 0644 /etc/cron.d/laravel-scheduler \
    && crontab /etc/cron.d/laravel-scheduler

# Copy entrypoint and set permissions
COPY ./docker/docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]
EXPOSE 9000
CMD ["php-fpm"]
