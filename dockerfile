# Use official PHP image with Apache
FROM php:8.2-apache

# Set working directory
WORKDIR /var/www/symfony

# Install necessary PHP extensions
RUN apt-get update && apt-get install -y \
    libzip-dev unzip git \
    && docker-php-ext-install zip pdo pdo_mysql

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy project files
COPY . /var/www/symfony

# Set correct permissions
RUN chown -R www-data:www-data /var/www/symfony

# Set the document root to the public directory
RUN sed -i 's|DocumentRoot /var/www/html|DocumentRoot /var/www/symfony/public|g' /etc/apache2/sites-available/000-default.conf

# Expose the default web server port
EXPOSE 80

# Run Composer install
RUN composer install --no-interaction --optimize-autoloader --no-dev

# Clear Symfony cache
RUN php bin/console cache:clear