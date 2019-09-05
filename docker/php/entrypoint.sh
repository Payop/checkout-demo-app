#!/bin/bash
set -eu

ln -fs /usr/share/zoneinfo/UTC /etc/localtime && dpkg-reconfigure -f noninteractive tzdata

composer install --no-scripts --no-interaction --optimize-autoloader

chown -R www-data:www-data /var/www

exec /usr/bin/supervisord -c /etc/supervisord.conf
