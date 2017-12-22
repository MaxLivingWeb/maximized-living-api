#!/bin/bash

# set the folder in which to run the installs
cd /var/www/html/

rm .env

python CreateEnvFile.py

rm CreateEnvFile.py

rm env.txt

# Run Artisan Commands if needed
if [ -f artisan ]; then
    echo "APP_KEY=" >> .env
    php artisan key:generate
fi
