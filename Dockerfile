FROM php:8.4-fpm

RUN apt-get update && apt-get install -y \
    libzip-dev \
    zip \
    unzip \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libssl-dev \
    ca-certificates

RUN docker-php-ext-install pdo pdo_mysql zip mbstring exif pcntl bcmath gd

# 🔥 penting untuk HTTPS request (Google API)
RUN update-ca-certificates

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www