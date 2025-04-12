ARG PHP_VERSION='8.3-fpm-bullseye'
FROM php:${PHP_VERSION}

## Diretório da aplicação
ARG APP_DIR=/var/www/app

## Versão da Lib do Redis para PHP
ARG REDIS_LIB_VERSION=5.3.7

# Dependências do sistema
RUN apt-get update -y && apt-get install -y --no-install-recommends \
    apt-utils \
    supervisor \
    zlib1g-dev \
    libzip-dev \
    unzip \
    libpng-dev \
    libpq-dev \
    libxml2-dev \
    nginx \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Extensões do PHP
RUN docker-php-ext-install mysqli pdo pdo_mysql pdo_pgsql pgsql session xml zip iconv simplexml pcntl gd fileinfo

# Instalação da Lib Redis para PHP
RUN pecl install redis-${REDIS_LIB_VERSION} \
    && docker-php-ext-enable redis

# Instalação do Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Configurações do Supervisor
COPY ./docker/supervisord/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY ./docker/supervisord/conf /etc/supervisord.d/

# Configurações do PHP
COPY docker/php/custom.ini /usr/local/etc/php/conf.d/custom.ini
COPY ./docker/php/extra-php-fpm.conf /etc/php8/php-fpm.d/www.conf

# Configurações do NGINX
RUN rm -rf /etc/nginx/sites-enabled/* && rm -rf /etc/nginx/sites-available/*
COPY ./docker/nginx/sites.conf /etc/nginx/sites-enabled/default.conf
COPY ./docker/nginx/error.html /var/www/html/error.html

# Configuração da aplicação
WORKDIR $APP_DIR

# Copia os arquivos do projeto primeiro
COPY --chown=www-data:www-data ./ ./

# Instala as dependências do Composer
RUN composer install --no-interaction --prefer-dist --optimize-autoloader --no-cache

# Permissões do projeto
RUN chown -R www-data:www-data $APP_DIR

# Configurações do Laravel Horizon
RUN composer require laravel/horizon
RUN php artisan horizon:install

# Otimização da aplicação
RUN php artisan clear-compiled \
    && php artisan optimize

# Comando final para iniciar o Supervisor
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/supervisord.conf"]