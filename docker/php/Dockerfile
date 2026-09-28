FROM php:7.4.8-fpm

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

WORKDIR /var/www/app
ADD entrypoint.sh /usr/local/bin/

CMD ["entrypoint.sh"]
