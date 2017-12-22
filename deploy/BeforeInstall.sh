#!/bin/bash

# set the folder in which to run the installs
cd /var/www/html/

# ensure composer is installed
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
php -r "if (hash_file('SHA384', 'composer-setup.php') === '544e09ee996cdf60ece3804abc52599c22b1f40f4323403c44d44fdfdd586475ca9813a858088ffbc1f233e9b180f061') { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('composer-setup.php'); } echo PHP_EOL;"
COMPOSER_HOME="/var/www/html" php composer-setup.php
php -r "unlink('composer-setup.php');"


echo $DEPLOYMENT_GROUP_NAME > env.txt

#yum -y install php71-mysqlnd

sudo pip install boto3
