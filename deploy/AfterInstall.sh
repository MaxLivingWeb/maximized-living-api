#!/bin/bash

set -e

# set the folder in which to run the installs
cd /var/www/html/

# Install composer packages
COMPOSER_HOME="/var/www/html/" php composer.phar install

echo "Hello!" > test.txt

if [ -f .env ]; then
    rm .env
fi

python CreateEnvFile.py

if [ -f CreateEnvFile.py ]; then
    rm CreateEnvFile.py
fi

if [ -f env.txt ]; then
    rm env.txt
fi

# Run Artisan Commands if needed
if [ -f artisan ]; then
    echo "APP_KEY=" >> .env
    php artisan key:generate
    php artisan migrate --force
fi

rm -f ./.htaccess

chown -R apache:apache /var/www/html
