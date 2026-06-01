#!/bin/bash

# Navigate to the backend directory
cd "$(dirname "$0")"

echo "🛑 Stopping Backend Services..."

# Function to stop service by search term
stop_service() {
    local name=$1
    local search_term=$2
    
    echo "Stopping $name..."
    pkill -f "$search_term"
}

# Stop the 4 artisan services
stop_service "SERVER" "php artisan serve"
stop_service "QUEUE" "php artisan queue:work"
# stop_service "REVERB" "php artisan reverb:start"
# stop_service "TELEGRAM" "php artisan telegram:ngrok"

echo "✅ All services stopped."
