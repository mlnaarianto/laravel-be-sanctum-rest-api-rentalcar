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
    ca-certificates \
    && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-install pdo pdo_mysql zip mbstring exif pcntl bcmath gd

# ✅ penting untuk HTTPS (Google OAuth)
RUN update-ca-certificates

# ✅ FIX UTAMA: pastikan /tmp proper (standard Linux)
RUN mkdir -p /tmp && chmod 1777 /tmp

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www