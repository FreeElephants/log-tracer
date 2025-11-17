ARG PHP_VERSION=8.4

FROM php:${PHP_VERSION}-cli

# Composer requirements begin
RUN apt-get update \
    && apt-get install -y \
    libzip-dev \
    unzip

RUN docker-php-ext-install zip

RUN pecl install xdebug-3.1.6 \
    && docker-php-ext-enable xdebug

RUN echo "xdebug.mode=coverage" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini

RUN echo "error_reporting = E_ALL" >> $PHP_INI_DIR/php.ini

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Composer requirements end

# Prepare image filesystem begin
WORKDIR /var/www
# Prepare image filesystem end
