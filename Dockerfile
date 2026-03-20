FROM php:8.2-fpm-alpine

LABEL maintainer="viniciuswerneck@gmail.com"
LABEL description="Raio-X de Vizinhança - Laravel Application"

RUN apk add --no-cache \
    nodejs \
    npm \
    git \
    curl \
    libpng-dev \
    oniguruma-dev \
    libxml2-dev \
    zip \
    unzip \
    supervisor \
    linux-headers \
    $PHPIZE_DEPS

RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd sockets

RUN pecl install redis && docker-php-ext-enable redis

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY composer.json composer.lock* ./

RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist

COPY . .

RUN composer dump-autoload --optimize

RUN npm ci && npm run build

RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

RUN chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

EXPOSE 9000

CMD ["php-fpm"]
