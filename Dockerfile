# Dockerfile
# Use a base image that includes PHP-FPM and Nginx
# richarvey/nginx-php-fpm is a popular choice for Laravel/PHP on Docker
# Choose a PHP version that matches your Laravel app's requirements (e.g., 8.2 or 8.3)
FROM richarvey/nginx-php-fpm:latest # Or specify a version like 8.2 or 8.3

# Set the working directory inside the container
WORKDIR /var/www/html

# Copy your composer.json and composer.lock first to leverage Docker's caching
COPY composer.json composer.lock ./

# Install Composer dependencies
RUN composer install --no-dev --optimize-autoloader

# Copy the rest of your application code
COPY . .

# Set permissions for storage and bootstrap/cache
# This is crucial for Laravel to write logs, sessions, and cache files
# These paths are relative to the WORKDIR inside the container
RUN chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

# Ensure the database directory and file are writable if your app modifies them
# If your database.sqlite is committed and not changed by the app, this might be less critical,
# but still good practice if your app needs to create it or write to it.
RUN chmod -R 775 database

# Expose the port that Nginx will be listening on (default for this image is 80)
EXPOSE 80

# Command to run when the container starts
# This image's default CMD is often `/start.sh` which properly starts Nginx and PHP-FPM.
# You generally don't need to specify `php artisan serve` when using Nginx/PHP-FPM.
CMD ["/start.sh"]
