FROM php:8.3-apache
RUN docker-php-ext-install pdo pdo_mysql   # Må til: uten pdo_mysql får ikke PHP koblet til MariaDB