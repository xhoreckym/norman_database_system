# Development Queue Setup

## Issue Identified

The EMPODAT CSV export jobs were not executing in the macOS development environment because:

1. **Export jobs** are queued on the `exports` queue
2. **Email notifications** are queued on the `default` queue  
3. The standard `php artisan queue:work` command only processes the `default` queue

## Solution

### Quick Fix (Single Command)
```bash
# For both queues (recommended for development)
php artisan queue:work --queue=exports,default

# Or run separately:
php artisan queue:work --queue=exports    # CSV export jobs
php artisan queue:work --queue=default    # Email notifications
```

### Development Script (Automated)
Use the provided script for easy development:

```bash
# Make executable (first time only)
chmod +x run-dev-queues.sh

# Run both queue workers
./run-dev-queues.sh
```

This script will:
- Start both `exports` and `default` queue workers
- Handle graceful shutdown with Ctrl+C
- Show status information
- Run continuously until stopped

## Queue Configuration

The application uses different queues for different job types:

| Queue | Job Types | Configuration |
|-------|-----------|---------------|
| `exports` | CSV export jobs (EmpodatCsvExportJob) | `AbstractCsvExportJob->onQueue('exports')` |
| `default` | Email notifications, general jobs | Laravel default |

## Verification

To verify jobs are processing correctly:

```bash
# Check pending jobs
php artisan tinker --execute="echo 'Exports queue: ' . DB::table('jobs')->where('queue', 'exports')->count() . PHP_EOL; echo 'Default queue: ' . DB::table('jobs')->where('queue', 'default')->count();"

# Check recent CSV files
ls -lat storage/app/exports/empodat/ | head -5

# Monitor queue status
php artisan horizon:status  # If using Horizon
# or
ps aux | grep "queue:work"  # Check running workers
```

## Production Considerations

In production, use a process manager like Supervisor or Horizon:

```bash
# Using Horizon (recommended)
php artisan horizon

# Or configure Supervisor for multiple queues
```

## Troubleshooting

### No jobs processing
1. Check if queue workers are running: `ps aux | grep queue:work`
2. Verify jobs are queued: Check database `jobs` table
3. Check for failed jobs: `php artisan queue:failed`
4. Verify queue configuration: `php artisan config:show queue`

### Jobs failing
1. Check logs: `tail -f storage/logs/laravel.log`
2. Check failed jobs table: `php artisan queue:failed`
3. Retry failed jobs: `php artisan queue:retry all`
