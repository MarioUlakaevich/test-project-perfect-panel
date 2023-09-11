# Используем официальный PHP образ
FROM php:8-fpm

# Используем настройки по умолчанию для продакшена
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

# Устанавливаем зависимости и расширения
RUN apt-get update && apt-get install -y libpng-dev libjpeg-dev libfreetype6-dev zip git unzip && \
    docker-php-ext-configure gd --with-freetype --with-jpeg && \
    docker-php-ext-install gd pdo pdo_mysql

# Устанавливаем Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Устанавливаем рабочую директорию
WORKDIR /var/www

# Копируем существующую директорию приложения
COPY . .

# Устанавливаем Laravel зависимости
RUN composer install

EXPOSE 9000
CMD ["php-fpm"]
