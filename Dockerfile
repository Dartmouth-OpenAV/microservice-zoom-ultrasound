FROM ubuntu:20.04

RUN apt-get update --fix-missing
RUN DEBIAN_FRONTEND=noninteractive apt-get install htop telnet w3m net-tools vim-tiny -y

# fixing timezone
RUN DEBIAN_FRONTEND=noninteractive apt-get install tzdata -y
ENV TZ=America/New_York
RUN ln -snf /usr/share/zoneinfo/$TZ /etc/localtime && echo $TZ > /etc/timezone

# Web Server
RUN DEBIAN_FRONTEND=noninteractive apt-get install apache2 php php-curl php-xml libapache2-mod-php -y
RUN rm /var/www/html/index.html
COPY _var_www_html /var/www/html
RUN chown -Rf www-data:www-data /var/www/html
RUN find /var/www/html -type f -exec chmod 440 {} \;
RUN find /var/www/html -type d -exec chmod 550 {} \;
RUN a2enmod rewrite
COPY _etc_apache2_sites-available_default.conf /etc/apache2/sites-available/000-default.conf

# sound playing capabilities
RUN DEBIAN_FRONTEND=noninteractive apt-get install alsa-base alsa-utils sox libsndfile1-dev -y
RUN mkdir /web
RUN chmod 777 web
COPY _gensound.php /gensound.php
RUN chmod 555 /gensound.php
COPY _playloop.php /playloop.php
RUN chmod 555 /playloop.php
COPY _checkcode.php /checkcode.php
RUN chmod 555 /checkcode.php

COPY _start.sh /start.sh
RUN chmod 555 /start.sh

ENTRYPOINT /start.sh