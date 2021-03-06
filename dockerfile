FROM php:7.4.3-apache

RUN apt-get update && apt-get install -y libpng-dev \
    libwebp-dev \
    libjpeg62-turbo-dev \
    libpng-dev libxpm-dev \
    libfreetype6-dev \
	libxml2-dev

RUN apt-get -y install \
        libmcrypt-dev \
        zlib1g-dev \
        libzip-dev \
        libonig-dev \
        graphviz

RUN apt-get install -y libcurl4-openssl-dev pkg-config libssl-dev

RUN docker-php-ext-install gd
RUN docker-php-ext-install mbstring
RUN docker-php-ext-install exif

RUN docker-php-ext-install mysqli
RUN docker-php-ext-install soap
RUN docker-php-ext-install zip


RUN pecl install mongodb
RUN pecl config-set php_ini /etc/php.ini
RUN echo "extension=mongodb.so" > $PHP_INI_DIR/conf.d/mongodb.ini
RUN echo "upload_max_filesize = 100M" > $PHP_INI_DIR/conf.d/upload.ini

RUN apt-get -y install git zip unzip
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
COPY ./composer.json /etc/composer.json
ENV COMPOSER /etc/composer.json
RUN composer install

WORKDIR /var/www/html
