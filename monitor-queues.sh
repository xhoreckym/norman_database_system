#!/bin/bash

# Production Queue Monitoring Script
# Monitors queue status and worker health

echo "=== Norman Database Queue Status ==="
echo "Timestamp: $(date)"
echo ""

# Check Docker containers
echo "📦 Docker Container Status:"
docker ps --filter "name=nds-app-queue" --format "table {{.Names}}\t{{.Status}}\t{{.Ports}}"
echo ""

# Check queue counts
echo "📊 Queue Job Counts:"
php artisan tinker --execute="
echo 'Default Queue: ' . DB::table('jobs')->where('queue', 'default')->count() . PHP_EOL;
echo 'High Priority: ' . DB::table('jobs')->where('queue', 'high')->count() . PHP_EOL;  
echo 'Medium Queue: ' . DB::table('jobs')->where('queue', 'medium')->count() . PHP_EOL;
echo 'Exports Queue: ' . DB::table('jobs')->where('queue', 'exports')->count() . PHP_EOL;
echo 'Failed Jobs: ' . DB::table('failed_jobs')->count() . PHP_EOL;
"

echo ""

# Check recent export jobs
echo "📋 Recent Export Jobs:"
php artisan tinker --execute="
\$exports = DB::table('export_downloads')
    ->orderBy('created_at', 'desc')
    ->limit(5)
    ->get(['filename', 'status', 'database_key', 'created_at']);
foreach(\$exports as \$export) {
    echo \$export->database_key . ' - ' . \$export->status . ' - ' . \$export->created_at . PHP_EOL;
}
"

echo ""

# Check worker processes
echo "🔧 Worker Process Status:"
docker exec nds-app-queue-default ps aux | grep queue:work | grep -v grep || echo "Default worker: Not running"
docker exec nds-app-queue-exports ps aux | grep queue:work | grep -v grep || echo "Export worker: Not running"  
docker exec nds-app-queue-medium ps aux | grep queue:work | grep -v grep || echo "Medium worker: Not running"

echo ""
echo "=== End Report ==="
