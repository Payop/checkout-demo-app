FROM 211725788353.dkr.ecr.eu-west-1.amazonaws.com/paydo-main:php8.1.2-fpm

#+++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
RUN echo 'PS1="\[\033[36m\]\u\[\033[m\]@\[\033[95;1m\]checkout.php:\[\033[34m\]\w\[\033[m\]\$ "' >> ~/.bashrc
RUN echo 'PS1="\[\033[36m\]\u\[\033[m\]@\[\033[95;1m\]checkout.php:\[\033[34m\]\w\[\033[m\]\$ "' >> /var/www/.bashrc
#+++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++

RUN apt-get update && apt-get install wkhtmltopdf ghostscript -y

COPY php.ini /usr/local/etc/php/conf.d/

RUN sed -i "s/opcache.validate_timestamps=0 ; (comment this in dev env)/;opcache.validate_timestamps=0 ; (comment this in dev env)/g" /usr/local/etc/php/conf.d/docker-php-ext-opcache.ini

RUN pecl install xdebug-3.1.3 \
    && docker-php-ext-enable xdebug  \
    && echo "xdebug.start_with_request=yes" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
    && echo "xdebug.remote_handler=dbgp" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
    && echo "xdebug.max_nesting_level=-1" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini

WORKDIR /var/www/app
ADD entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/entrypoint.sh

CMD ["entrypoint.sh"]
