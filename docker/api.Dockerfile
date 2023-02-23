FROM php:8.2-fpm-alpine3.17

RUN apk update
RUN apk add --no-cache $PHPIZE_DEPS linux-headers git nginx zlib-dev

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

COPY data/start.sh /opt/start.sh
RUN dos2unix /opt/start.sh

COPY data/nginx.conf /etc/nginx/nginx.conf
RUN dos2unix /etc/nginx/nginx.conf

RUN rm -rf /etc/nginx/http.d/*
COPY data/api.nginx.conf /etc/nginx/http.d/api.nginx.conf
RUN dos2unix /etc/nginx/http.d/api.nginx.conf

COPY data/webgrind.nginx.conf /etc/nginx/http.d/webgrind.nginx.conf
RUN dos2unix /etc/nginx/http.d/webgrind.nginx.conf

RUN cp /usr/local/etc/php/php.ini-development /usr/local/etc/php/php.ini
RUN sed -i "s/\;\?\\s\?expose_php =.*/expose_php = Off/" /usr/local/etc/php/php.ini
RUN sed -i "s/\;\?\\s\?error_reporting =.*/error_reporting = E_ALL/" /usr/local/etc/php/php.ini
RUN sed -i "s/\;\?\\s\?display_startup_errors =.*/display_startup_errors = On/" /usr/local/etc/php/php.ini
RUN sed -i "s/\;\?\\s\?display_errors =.*/display_errors = On/" /usr/local/etc/php/php.ini
RUN sed -i "s/\;\?\\s\?session.auto_start =.*/session.auto_start = Off/" /usr/local/etc/php/php.ini
RUN sed -i "s/\;\?\\s\?session.gc_probability =.*/session.gc_probability = 0/" /usr/local/etc/php/php.ini
RUN sed -i "s/\;\?\\s\?session.use_cookies =.*/session.use_cookies = Off/" /usr/local/etc/php/php.ini
RUN echo 'xdebug.output_dir=/tmp' >> /usr/local/etc/php/php.ini
RUN echo 'xdebug.profiler_append=0' >> /usr/local/etc/php/php.ini
RUN echo 'xdebug.mode=off' >> /usr/local/etc/php/php.ini

RUN pecl channel-update pecl.php.net
RUN pecl install xdebug && docker-php-ext-enable xdebug

RUN git clone https://github.com/daniel1919-00/webgrind.git /profiler
RUN cd /profiler && make
RUN chmod 777 /profiler

RUN rm -rf /tmp/*

WORKDIR /api

ENTRYPOINT [ "sh", "/opt/start.sh" ]
