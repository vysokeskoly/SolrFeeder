#!/usr/bin/env bash

composer install --optimize-autoloader --no-dev &&

export BUILD_NUMBER=666 &&
php robo.phar build:deb
