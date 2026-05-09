FROM php:8.2-apache

# Install System Dependencies
RUN apt-get update && apt-get install -y \
    libpq-dev \
    libonig-dev \
    libzip-dev \
    zip \
    unzip

# Install PHP Extensions
# pcntl is REQUIRED for Requirement #11 (Multithreading/Forking)
RUN docker-php-ext-install pdo pdo_mysql pcntl mbstring zip

# Install Redis Extension for caching
RUN pecl install redis && docker-php-ext-enable redis

# Enable Apache Mod Rewrite
RUN a2enmod rewrite

# Set Working Directory
WORKDIR /var/www/html

# Expose Port 80
EXPOSE 80