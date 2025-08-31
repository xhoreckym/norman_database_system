#!/bin/bash

# Script to run queue worker with proper timeout settings for large export jobs
# Use this instead of queue:listen for production

echo "Starting queue worker with extended timeout for large exports..."
echo "Timeout: 7200 seconds (2 hours)"
echo "Memory: 512MB"
echo "Tries: 3"
echo ""

php artisan queue:work \
    --timeout=7200 \
    --memory=512 \
    --sleep=3 \
    --tries=3 \
    --backoff=60,300,900 \
    --verbose

echo "Queue worker stopped."
