#!/bin/bash

set -e

KEY=Environment

INSTANCE_ID=$(curl -s http://169.254.169.254/latest/meta-data/instance-id)
REGION=$(curl -s http://169.254.169.254/latest/dynamic/instance-identity/document | grep region | awk -F\" '{print $4}')

# Grab tag value
TAG_VALUE=$(aws ec2 describe-tags --filters "Name=resource-id,Values=$INSTANCE_ID" "Name=key,Values=$KEY" --region=$REGION --output=text | cut -f5)

echo -e "<IfModule mod_setenvif.c>\nSetEnvIf Host "^" IGENV=$TAG_VALUE\n</IfModule>\n\n$(cat ./.htaccess)" > ./.htaccess
