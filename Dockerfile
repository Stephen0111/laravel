# Use base image with PHP-FPM + Nginx
FROM richarvey/nginx-php-fpm:php8.2-alpine

# Set working directory
WORKDIR /var/www/html

# Copy composer files first to optimize Docker layer caching
COPY composer.json composer.lock ./

# Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader

# Copy application files into the container
COPY . .

# Set proper permissions for Laravel
RUN chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache \
    && chmod -R 775 database

# Expose default port
EXPOSE 80

# Start Nginx and PHP-FPM
CMD ["/start.sh"]
