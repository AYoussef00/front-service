FROM php:8.4-apache

WORKDIR /var/www/html

RUN apt-get update && apt-get install -y \
    git curl libpng-dev libonig-dev libxml2-dev libzip-dev zip unzip libpq-dev nodejs npm \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

RUN a2enmod rewrite
COPY docker/apache-config.conf /etc/apache2/sites-available/000-default.conf

COPY . .

# Copy .env and generate key
COPY env.example .env
RUN php artisan key:generate

RUN composer install --no-dev --optimize-autoloader
RUN npm ci && npm run build

RUN chown -R www-data:www-data /var/www/html \
    && find /var/www/html/storage /var/www/html/bootstrap/cache /var/www/html/public/build -type d -exec chmod 755 {} \; \
    && find /var/www/html/storage /var/www/html/bootstrap/cache /var/www/html/public/build -type f -exec chmod 644 {} \;

EXPOSE 80
CMD ["apache2-foreground"]
