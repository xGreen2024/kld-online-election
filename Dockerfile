# Use official PHP with Apache web server
FROM php:8.2-apache

# Install MySQL extension required for PHP database connections
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Copy your application files to Apache's default root directory
COPY . /var/www/html/

# Enable Apache rewrite module
RUN a2enmod rewrite

# Expose default HTTP port
EXPOSE 80
