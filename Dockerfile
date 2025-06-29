# Dockerfile
# Use a base image that includes PHP-FPM and Nginx
# richarvey/nginx-php-fpm is a popular choice for Laravel/PHP on Docker
# Choose a PHP version that matches your Laravel app's requirements (e.g., 8.2 or 8.3)
FROM richarvey/nginx-php-fpm:latest
# Line 5 (where the error was) should now be just the FROM instruction.
# The comment that was previously on line 5 has been moved to its own line (or removed).

# Set the working directory inside the container
WORKDIR /var/www/html

# Copy your composer.json and composer.lock first to leverage Docker's caching
COPY composer.json composer.lock ./

# Install Composer dependencies
RUN composer install --no-dev --optimize-autoloader

# Copy the rest of your application code
COPY . .

# Set permissions for storage and bootstrap/cache
RUN chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

# Ensure the database directory and file are writable
RUN chmod -R 775 database

# Expose the port that Nginx will be listening on (default for this image is 80)
EXPOSE 80

# Command to run when the container starts
CMD ["/start.sh"]
