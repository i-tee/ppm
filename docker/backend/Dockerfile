# docker/backend/Dockerfile
# Используем официальный образ PHP 8.3 с FPM для обработки PHP-кода
FROM php:8.3-fpm

# Устанавливаем системные зависимости и PHP-расширения, критичные для Laravel
RUN apt-get update && apt-get install -y \
    build-essential \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    zip \
    unzip \
    git \
    curl \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip

# Устанавливаем Composer (менеджер зависимостей PHP)
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Создаем не-root пользователя для безопасности (совпадает с вашим пользователем WSL)
RUN useradd -G www-data,root -u 1000 -d /home/devuser devuser
RUN mkdir -p /home/devuser/.composer && chown -R devuser:devuser /home/devuser

# Устанавливаем рабочую директорию
WORKDIR /var/www

# Переключаемся на созданного пользователя
USER devuser