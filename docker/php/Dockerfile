FROM 211725788353.dkr.ecr.eu-west-1.amazonaws.com/paydo-main:php7.4.8-fpm

#+++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
RUN echo 'PS1="\[\033[36m\]\u\[\033[m\]@\[\033[95;1m\]checkout.php:\[\033[34m\]\w\[\033[m\]\$ "' >> ~/.bashrc
RUN echo 'PS1="\[\033[36m\]\u\[\033[m\]@\[\033[95;1m\]checkout.php:\[\033[34m\]\w\[\033[m\]\$ "' >> /var/www/.bashrc
#+++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++

COPY www.conf /usr/local/etc/php/conf.d/

RUN echo "opcache.revalidate_freq=0" >> /usr/local/etc/php/conf.d/docker-php-ext-opcache.ini \
    && echo "opcache.validate_timestamps=0 ; (comment this in dev env)" >> /usr/local/etc/php/conf.d/docker-php-ext-opcache.ini \
    && echo "opcache.max_accelerated_files=50000"  >> /usr/local/etc/php/conf.d/docker-php-ext-opcache.ini \
    && echo "opcache.memory_consumption=1512"  >> /usr/local/etc/php/conf.d/docker-php-ext-opcache.ini \
    && echo "opcache.interned_strings_buffer=16"  >> /usr/local/etc/php/conf.d/docker-php-ext-opcache.ini \
    && echo "opcache.fast_shutdown=1"  >> /usr/local/etc/php/conf.d/docker-php-ext-opcache.ini \
    && echo "opcache.consistency_checks=1"  >> /usr/local/etc/php/conf.d/docker-php-ext-opcache.ini \
    && echo "opcache.save_comments=1"  >> /usr/local/etc/php/conf.d/docker-php-ext-opcache.ini

RUN pecl install xdebug \
    && docker-php-ext-enable xdebug \
    && echo "xdebug.remote_enable=1" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
    && echo "xdebug.remote_autostart=1" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
    && echo "xdebug.remote_connect_back=0" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
    && echo "xdebug.remote_port=9000" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
    && echo "xdebug.remote_handler=dbgp" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
    && echo "xdebug.max_nesting_level=-1" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini

WORKDIR /var/www/app
ADD entrypoint.sh /usr/local/bin/

CMD ["entrypoint.sh"]
