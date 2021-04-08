ARG PHP_VERSION=8

FROM php:${PHP_VERSION}-fpm-alpine AS wo_php

ADD docker/php/install_composer.sh /install_composer.sh
RUN sh /install_composer.sh && rm -f /install_composer.sh

RUN apk add --update-cache \
    lftp \
  && rm -rf /var/cache/apk/*
