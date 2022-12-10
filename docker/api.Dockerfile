FROM alpine:3.17

RUN apk add --no-cache \
    autoconf \
    dpkg-dev \
    dpkg \
    file \
    g++ \
    gcc \
    libc-dev \
    make \
    pkgconf \
    re2c \
    nginx \
    dos2unix \
    curl \
    php81 \
    php81-fpm \
    php81-dev \
    php81-pdo \
    php81-pdo_pgsql \
    php81-pdo_mysql \
    php81-pear \
    php81-curl \
    php81-ctype \
    php81-iconv \
    php81-intl \
    php81-json \
    php81-mbstring \
    php81-phar \
    php81-opcache \
    php81-openssl \
    git \
    openssl \
    linux-headers

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

RUN sed -i "s/\;\?\\s\?error_reporting =.*/error_reporting = E_ALL/" /etc/php81/php.ini
RUN sed -i "s/\;\?\\s\?display_startup_errors =.*/display_startup_errors = On/" /etc/php81/php.ini
RUN sed -i "s/\;\?\\s\?display_errors =.*/display_errors = On/" /etc/php81/php.ini
RUN echo 'zend_extension=xdebug.so' >> /etc/php81/php.ini
RUN echo 'xdebug.output_dir=/tmp' >> /etc/php81/php.ini
RUN echo 'xdebug.profiler_append=0' >> /etc/php81/php.ini
RUN echo 'xdebug.mode=off' >> /etc/php81/php.ini

RUN pecl channel-update pecl.php.net
RUN pecl install xdebug
RUN git clone https://github.com/jokkedk/webgrind /profiler

ENTRYPOINT [ "sh", "/opt/start.sh" ]
