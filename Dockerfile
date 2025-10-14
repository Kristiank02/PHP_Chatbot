FROM php:8.3-apache

# Install PDO MySQL extension
RUN docker-php-ext-install pdo pdo_mysql

# Create directories with proper permissions for volume mounts
RUN mkdir -p /var/www/src && chown -R www-data:www-data /var/www/html /var/www/src