cd /api && composer install
sed -i "s/\;\?\\s\?xdebug.mode=.*/xdebug.mode=$XDEBUG_MODE/" /usr/local/etc/php/php.ini
php-fpm -D
nginx -g "daemon off;"