#!/bin/bash

cd /var/www/html/

#yum -y install php71-mysqlnd

sudo pip install boto3

# Install composer packages
COMPOSER_HOME="/var/www/html/" php composer.phar install

echo "Hello!" > test.txt
