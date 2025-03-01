FROM php:8.2-fpm

# Install dependencies
RUN apt-get update && apt-get install -y \
    libicu-dev \
    libpq-dev \
    && docker-php-ext-install intl pdo pdo_mysql opcache

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www

# Copy Symfony project
COPY . /var/www

# Install dependencies
RUN composer install --no-dev --optimize-autoloader

# Expose the port for PHP-FPM
EXPOSE 9000

CMD ["php-fpm"]