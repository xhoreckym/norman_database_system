#!/bin/bash

# Development queue worker script
# This script runs both the exports queue (for CSV jobs) and default queue (for emails)

echo "🚀 Starting NORMAN Database development queue workers..."
echo "📧 Processing exports queue (CSV jobs) and default queue (emails)"
echo "⏹️  Press Ctrl+C to stop"
echo ""

# Function to handle cleanup on script exit
cleanup() {
    echo ""
    echo "🛑 Stopping queue workers..."
    # Kill all background jobs
    jobs -p | xargs -r kill
    echo "✅ Queue workers stopped"
    exit 0
}

# Set up signal handlers
trap cleanup SIGINT SIGTERM

# Start queue workers in background
php artisan queue:work --queue=exports --sleep=3 --tries=3 --max-time=3600 &
EXPORTS_PID=$!

php artisan queue:work --queue=default --sleep=3 --tries=3 --max-time=3600 &
DEFAULT_PID=$!

echo "✅ Exports queue worker started (PID: $EXPORTS_PID)"
echo "✅ Default queue worker started (PID: $DEFAULT_PID)"
echo ""
echo "📊 Queue Status:"
echo "   - Exports queue: Processing CSV export jobs"
echo "   - Default queue: Processing email notifications"
echo ""

# Wait for background processes
wait
