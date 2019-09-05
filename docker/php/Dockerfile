FROM php:7.3-fpm

ENV DEBIAN_FRONTEND noninteractive
RUN TERM=xterm

#+++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
RUN echo 'PS1="\[\033[36m\]\u\[\033[m\]@\[\033[95;1m\]chckout.php:\[\033[34m\]\w\[\033[m\]\$ "' >> ~/.bashrc
RUN echo 'PS1="\[\033[36m\]\u\[\033[m\]@\[\033[95;1m\]chckout.php:\[\033[34m\]\w\[\033[m\]\$ "' >> /var/www/.bashrc
#+++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++

RUN apt-get update \
    && apt-get install -y --no-install-recommends locales tzdata \
    && locale-gen en_US.UTF-8

RUN rm -rf /var/lib/apt/lists/*

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        software-properties-common \
        gettext \
        libfreetype6-dev \
        libmcrypt-dev \
        libpng-dev \
        libjpeg-dev \
        libicu-dev \
        libpq-dev \
        libzip-dev \
        libxml2-dev \
        libsqlite3-dev \
        libxslt-dev \
        wget \
        curl \
        git \
        vim \
        zip \
        unzip \
        supervisor

RUN ln -sf /dev/stderr /var/log/php-fpm.log \
    && apt-get clean \
    && rm -rf /tmp/* /var/tmp/* /var/lib/apt/lists/*

RUN docker-php-ext-configure gd --with-png-dir=/usr --with-jpeg-dir=/usr \
	&& docker-php-ext-install \
        bcmath \
        ctype \
        dom \
        exif \
        gd \
        gettext \
        intl \
        json \
        mbstring \
        opcache \
        pdo \
        pdo_mysql \
        pdo_pgsql \
        pgsql \
        pdo_sqlite \
        sockets \
        simplexml \
        xml \
        xmlrpc \
        xsl \
        zip

RUN rm /usr/local/etc/php-fpm.d/www.conf
COPY www.conf /usr/local/etc/php-fpm.d/

RUN echo "opcache.revalidate_freq=0" >> /usr/local/etc/php/conf.d/docker-php-ext-opcache.ini \
    && echo "opcache.validate_timestamps=0 ; (comment this in dev env)" >> /usr/local/etc/php/conf.d/docker-php-ext-opcache.ini \
    && echo "opcache.max_accelerated_files=50000"  >> /usr/local/etc/php/conf.d/docker-php-ext-opcache.ini \
    && echo "opcache.memory_consumption=1512"  >> /usr/local/etc/php/conf.d/docker-php-ext-opcache.ini \
    && echo "opcache.interned_strings_buffer=16"  >> /usr/local/etc/php/conf.d/docker-php-ext-opcache.ini \
    && echo "opcache.fast_shutdown=1"  >> /usr/local/etc/php/conf.d/docker-php-ext-opcache.ini \
    && echo "opcache.consistency_checks=1"  >> /usr/local/etc/php/conf.d/docker-php-ext-opcache.ini \
    && echo "opcache.save_comments=1"  >> /usr/local/etc/php/conf.d/docker-php-ext-opcache.ini

RUN curl -sS https://getcomposer.org/installer | php -- --filename=composer --install-dir=/usr/local/bin
RUN export PATH="~/.composer/vendor/bin/:$PATH"

RUN mkdir -p /var/run/php

COPY supervisord.conf /etc/supervisord.conf
ADD entrypoint.sh /usr/local/bin/

RUN sed -i "s/opcache.validate_timestamps=0 ; (comment this in dev env)/;opcache.validate_timestamps=0 ; (comment this in dev env)/g" /usr/local/etc/php/conf.d/docker-php-ext-opcache.ini

RUN pecl install xdebug \
    && docker-php-ext-enable xdebug \
    && echo "xdebug.remote_enable=1" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
    && echo "xdebug.remote_autostart=1" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
    && echo "xdebug.remote_connect_back=0" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
    && echo "xdebug.remote_port=9000" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
    && echo "xdebug.remote_handler=dbgp" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
    && echo "xdebug.max_nesting_level=-1" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini

WORKDIR /var/www/app

CMD ["entrypoint.sh"]
