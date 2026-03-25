#!/bin/bash

# Navigate to the backend directory
cd "$(dirname "$0")"

echo "🧹 Cleaning Laravel Services & Optimizations..."

# Clear all cached configurations and optimize the application
echo "Cleaning artisan caches..."
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan clear-compiled
php artisan optimize:clear

# Optional: Dump composer autoload if requested or necessary
# echo "Refreshing composer autoload..."
# composer dump-autoload -o

echo "✨ Backend cleaned successfully!"
