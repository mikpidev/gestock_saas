#!/bin/bash

# Project Path Navigation
PROJECT_PATH="/var/www/html" # Change this to your project's root directory

#if script is located at the root of the project
cd "$(dirname "$0")"

#if not use the following line to navigate to project root
#cd /path/to/your/project Example: cd /var/www/html/my-laravel-app

set -e # Exit immediately if a command exits with a non-zero status

echo "Starting deployment process..."


echo "Fetching latest changes from git..."
git fetch --all
git reset --hard origin/main

echo "Installing PHP dependencies with Composer..."
composer install --no-dev --optimize-autoloader

echo "Running database migrations..."
php artisan migrate --force

echo "Compiling front-end"
npm run build

echo "Clearing and caching Laravel configurations..."
php artisan optimize:clear

echo "Optimizing application..."
php artisan optimize || true

echo "Setting permissions..."
chown -R www-data:www-data $PROJECT_PATH #adjust path if necessary
chmod -R 775 $PROJECT_PATH/storage $PROJECT_PATH/bootstrap/cache #adjust path if necessary
chmod +x $PROJECT_PATH/bash_scripts/gestockbackup.sh

echo "Checking Apache server status..."
systemctl is-active apache2 || echo "Apache is not running or inactive"

echo "Restarting Apache server..."
systemctl restart apache2

echo "Verifying Apache server status..."
systemctl is-active apache2 && echo "Apache is running ✅"

echo "Deployment process completed successfully."
echo "Don't forget to add env variables if needed and test the application."
