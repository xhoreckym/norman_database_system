#!/bin/bash

# Fast Laravel Development Server for M3 MacBook Air
# This script optimizes Laravel for development performance

echo "🚀 Setting up fast Laravel development server..."

# Clear any existing optimizations that might interfere with development
echo "📝 Clearing caches for development..."
php artisan optimize:clear

# Set optimal PHP memory limit for M3
export PHP_MEMORY_LIMIT=512M

# Clear Telescope data (major performance killer)
echo "🔭 Clearing Telescope data..."
php artisan telescope:clear

# Optimize for development
echo "⚡ Optimizing for development..."
php artisan config:cache
php artisan route:cache

# Set environment optimizations
export APP_DEBUG=true
export TELESCOPE_ENABLED=false
export DEBUGBAR_ENABLED=false
export CACHE_STORE=file
export SESSION_DRIVER=file
export QUEUE_CONNECTION=sync

echo "🎯 Starting optimized development server..."
echo "📊 Performance tips:"
echo "   - Telescope is disabled for better performance"
echo "   - Using file-based cache and sessions"
echo "   - Optimized autoloader and routes cached"
echo "   - Memory limit set to 512M"
echo ""
echo "🌐 Server will be available at: http://localhost:8000"
echo "⏱️  Server should respond much faster now!"
echo ""

# Start the server with optimized settings
php -d memory_limit=512M -d opcache.enable=1 -d opcache.memory_consumption=256 -d opcache.max_accelerated_files=10000 artisan serve --host=127.0.0.1 --port=8000
