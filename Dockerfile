FROM ubuntu:24.04

# Install prerequisites
RUN apt-get update && apt-get install -y \
    ca-certificates \
    curl \
    lsb-release \
    && rm -rf /var/lib/apt/lists/*

# Add the PHP repository and its signing key
RUN curl -fsSL \
    https://packages.sury.org/debsuryorg-archive-keyring.deb \
    -o /tmp/debsuryorg-archive-keyring.deb \
    && dpkg -i /tmp/debsuryorg-archive-keyring.deb \
    && rm /tmp/debsuryorg-archive-keyring.deb \
    && printf 'Types: deb\nURIs: https://packages.sury.org/php/\nSuites: trixie\nComponents: main\nSigned-By: /usr/share/keyrings/debsuryorg-archive-keyring.gpg\n' \
       > /etc/apt/sources.list.d/php.sources


RUN apt-get update --fix-missing
RUN DEBIAN_FRONTEND=noninteractive apt-get install htop telnet w3m net-tools vim-tiny -y

# fixing timezone
RUN DEBIAN_FRONTEND=noninteractive apt-get install tzdata -y
ENV TZ=America/New_York
RUN ln -snf /usr/share/zoneinfo/$TZ /etc/localtime && echo $TZ > /etc/timezone

# Web Server
#RUN DEBIAN_FRONTEND=noninteractive apt-get install apache2 php php-curl php-xml libapache2-mod-php -y
RUN apt-get update && \
    DEBIAN_FRONTEND=noninteractive apt-get install -y --no-install-recommends \
        apache2 \
        php8.5 \
        php8.5-curl \
        php8.5-xml \
        libapache2-mod-php8.5 \
    && rm -rf /var/lib/apt/lists/*
RUN rm /var/www/html/index.html
COPY _var_www_html /var/www/html
RUN chown -Rf www-data:www-data /var/www/html
RUN find /var/www/html -type f -exec chmod 440 {} \;
RUN find /var/www/html -type d -exec chmod 550 {} \;
RUN a2enmod rewrite
COPY _etc_apache2_sites-available_default.conf /etc/apache2/sites-available/000-default.conf

# sound playing capabilities
#RUN DEBIAN_FRONTEND=noninteractive apt-get install alsa-base alsa-utils sox libsndfile1-dev -y
RUN apt-get update && \
    DEBIAN_FRONTEND=noninteractive apt-get install -y --no-install-recommends \
        alsa-base \
        alsa-utils \
        sox \
        libsndfile1-dev \
        libttspico-utils \
        libsox-fmt-mp3 \
    && rm -rf /var/lib/apt/lists/*
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
