cd /api && composer install
sed -i "s/\;\?\\s\?xdebug.mode=.*/xdebug.mode=$XDEBUG_MODE/" /etc/php81/php.ini
rm -rf /tmp/*
cd /profiler && git fetch && git reset --hard && git pull
php-fpm81
nginx -g "daemon off;"