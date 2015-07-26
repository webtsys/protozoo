#!/bin/sh

echo "Installing composer..."

php -r "readfile('https://getcomposer.org/installer');" | php

sudo mv composer.phar /usr/local/bin/composer






