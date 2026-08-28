FROM php:8.2-apache

# System deps untuk Laravel + phpspreadsheet + Vite
RUN apt-get update && apt-get install -y \
    git curl zip unzip libzip-dev libpng-dev libonig-dev libxml2-dev libcurl4-openssl-dev \
    nodejs npm \
    && docker-php-ext-install pdo_mysql mbstring zip gd bcmath \
    && a2enmod rewrite \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copy dependency files first (cache)
COPY composer.json composer.lock package.json package-lock.json* vite.config.js ./
COPY resources ./resources
COPY public ./public

RUN composer install --no-dev --optimize-autoloader --no-interaction --no-scripts || true

COPY . .

# Build Vite (Tailwind) + install php deps final
RUN npm ci --silent || npm install --silent \
    && npm run build \
    && composer install --no-dev --optimize-autoloader --no-interaction \
    && php artisan storage:link || true \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

# Apache DocumentRoot -> public/
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
    && sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

EXPOSE 80

# Start script: migrate + cache + apache
CMD bash -c "php artisan migrate --force || true; php artisan config:cache; php artisan route:cache; php artisan view:cache; apache2-foreground"
