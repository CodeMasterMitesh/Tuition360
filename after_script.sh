#!/bin/bash
# after_script.sh
# This script updates dependencies and runs Laravel migrations & seeders

# Navigate to project directory
cd /home/u312039715/domains/aivoranextgen.com/public_html/campuslite || exit

echo "Running composer update..."
php composer.phar update

echo "All tasks completed successfully!"
