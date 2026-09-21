# ============================================================
# Backend — Laravel 11 + PHP 8.3
# ============================================================

FROM php:8.3-cli

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpq-dev \
    libzip-dev \
    unzip \
    && docker-php-ext-install pdo pdo_pgsql zip bcmath \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app

# Copy composer files and install dependencies
COPY composer.json composer.lock* ./
RUN composer config "policy.advisories.block" false
RUN composer install --no-dev --no-scripts --no-autoloader --no-security-blocking 2>/dev/null || true

# Copy application
COPY . .

# Create storage and bootstrap/cache directories
RUN mkdir -p storage/backups \
    && mkdir -p storage/framework/{cache,sessions,views} \
    && mkdir -p storage/logs \
    && mkdir -p bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

# Install dependencies and generate autoloader
RUN composer install --no-dev --optimize-autoloader --no-security-blocking

EXPOSE 8000

CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
