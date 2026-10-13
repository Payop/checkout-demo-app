#!/bin/bash
#set -eu

#ln -fs /usr/share/zoneinfo/UTC /etc/localtime && dpkg-reconfigure -f noninteractive tzdata

if ! command -v usermod >/dev/null 2>&1;
then
  apk add --no-cache shadow
fi

usermod -u "$UID_VAR" www-data
groupmod -g "$GID_VAR" www-data
#First INIT
if ! command -v composer >/dev/null 2>&1;
then
  echo "Installing Composer 2.2"
  php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
  php -r "if (hash_file('sha384', 'composer-setup.php') === 'c8b085408188070d5f52bcfe4ecfbee5f727afa458b2573b8eaaf77b3419b0bf2768dc67c86944da1544f06fa544fd47') { echo 'Installer verified'.PHP_EOL; } else { echo 'Installer corrupt'.PHP_EOL; unlink('composer-setup.php'); exit(1); }"
  php composer-setup.php --2.2 --install-dir=/usr/local/bin/ --filename=composer
  php -r "unlink('composer-setup.php');"
fi

if ! command -v git >/dev/null 2>&1;
then
  apk add --no-cache git
fi

if ! command -v zip >/dev/null 2>&1;
then
  apk add --no-cache zip
fi

if [ ! -d /var/www/app/vendor ];
then
  composer install --ignore-platform-reqs
fi

chown -R www-data:www-data /var/www
chmod -R 0777 /var/www/app

/usr/local/sbin/php-fpm --nodaemonize
