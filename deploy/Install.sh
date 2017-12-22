#!/bin/bash

#yum -y install php71-mysqlnd

pip install boto3

# Install composer packages
COMPOSER_HOME="/var/www/html" php composer.phar install
