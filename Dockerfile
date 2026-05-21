FROM php:8.4-fpm-alpine AS base

# System dependencies
RUN apk add --no-cache \
    bash \
    git \
    curl \
    icu-dev \
    libzip-dev \
    zip \
    unzip \
    sqlite \
    sqlite-dev \
    oniguruma-dev \
    libpng-dev \
    libxml2-dev \
    nodejs \
    npm \
    shadow \
    && docker-php-ext-install \
        pdo \
        pdo_sqlite \
        pdo_mysql \
        mbstring \
        intl \
        zip \
        bcmath \
        gd \
        opcache \
    && rm -rf /var/cache/apk/*

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Match host UID/GID so volume mounts don't fight over permissions
ARG UID=1000
ARG GID=1000
RUN if getent group ${GID} > /dev/null; then \
        echo "group ${GID} exists"; \
    else \
        addgroup -g ${GID} app; \
    fi \
    && if getent passwd ${UID} > /dev/null; then \
        echo "user ${UID} exists"; \
    else \
        adduser -u ${UID} -G $(getent group ${GID} | cut -d: -f1) -s /bin/bash -D app; \
    fi

WORKDIR /var/www/html

# Install PHP dependencies first (better layer caching)
COPY composer.json composer.lock ./
RUN composer install --no-interaction --no-scripts --no-autoloader --prefer-dist

# Install Node dependencies
COPY package.json package-lock.json ./
RUN npm ci --ignore-scripts

# Copy application source
COPY . .

# Build front-end assets and finalize autoload
RUN npm run build \
    && composer dump-autoload --optimize \
    && composer run-script post-autoload-dump || true

# Storage and bootstrap permissions
RUN mkdir -p storage/framework/{cache,sessions,testing,views} storage/logs bootstrap/cache database \
    && touch database/database.sqlite \
    && chown -R ${UID}:${GID} /var/www/html \
    && chmod -R 775 storage bootstrap/cache database

COPY docker/entrypoint.sh /usr/local/bin/entrypoint
RUN chmod +x /usr/local/bin/entrypoint

EXPOSE 9000

ENTRYPOINT ["entrypoint"]
CMD ["php-fpm"]
