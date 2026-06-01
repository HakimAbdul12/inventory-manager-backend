#!/bin/bash

# Navigate to the backend directory
cd "$(dirname "$0")"

# Print startup message
echo "🚀 Backend Startup: Opening 4 tabs in separate terminal windows..."

# Function to start a tab with a command
start_tab() {
    local title=$1
    local cmd=$2
    # Open in a new tab if a window exists, otherwise starts a new window
    gnome-terminal --tab --title="$title" -- bash -c "echo '🖥 Starting $title...'; $cmd; exec bash"
}

# Start all 4 services
start_tab "SERVER" "php artisan serve"
start_tab "QUEUE" "php artisan queue:work --queue=inventory,default"
# start_tab "REVERB" "php artisan reverb:start"
# start_tab "TELEGRAM" "php artisan telegram:ngrok"

echo "✅ All services launched in separate tabs."
echo "You can use this terminal to run your additional commands."
