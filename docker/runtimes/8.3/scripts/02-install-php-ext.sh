#!/bin/bash

apt-get install -y php8.3-mysql
apt-get install -y php8.3-pgsql
apt-get install -y php8.3-sqlite3
apt-get install -y php8.3-gd
apt-get install -y php8.3-curl
apt-get install -y php8.3-mbstring
apt-get install -y php8.3-xml
apt-get install -y php8.3-zip
apt-get install -y php8.3-bcmath
apt-get install -y php8.3-soap
apt-get install -y php8.3-intl
apt-get install -y php8.3-readline
apt-get install -y php8.3-ldap
apt-get install -y php8.3-msgpack
apt-get install -y php8.3-igbinary
apt-get install -y php8.3-redis
apt-get install -y php8.3-swoole
apt-get install -y php8.3-memcached
apt-get install -y php8.3-pcov
apt-get install -y php8.3-imagick
apt-get install -y php8.3-xdebug
apt-get install -y php8.3-calendar
apt-get install -y php8.3-ctype
apt-get install -y php8.3-pcntl
apt-get install -y php8.3-bz2
apt-get install -y php8.3-ftp
apt-get install -y php8.3-iconv

docker-php-ext-configure pdo_odbc --with-pdo-odbc=unixODBC,/usr \
 && docker-php-ext-install pdo_odbc
