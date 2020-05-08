FROM php:7.3-fpm

RUN apt-get install && apt-get clean && apt-get -y autoremove

RUN ln -s /usr/local/bin/php /bin/php

RUN usermod -u 1000 www-data

RUN usermod -s /bin/bash -d /home/www-data www-data && mkdir -p /home/www-data && chown -R www-data.www-data /home/www-data

WORKDIR /src

COPY php/ /usr/local/etc/

CMD ["php-fpm"]
