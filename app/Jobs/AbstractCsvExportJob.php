<?php

namespace App\Jobs;

use Exception;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use App\Models\Backend\QueryLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use App\Models\Backend\ExportDownload;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

abstract class AbstractCsvExportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    
    protected $queryLogId;
    protected $user;
    protected $maxExecutionTime = 1800; // 30 minutes
    protected $initialBatchSize = 500; // Start with smaller batches
    protected $maxBatchSize = 2000; // Can grow to larger batches
    protected $currentBatchSize;
    
    /**
     * The number of seconds the job can run before timing out.
     */
    public $timeout = 7200; // 2 hours
    
    /**
     * The number of times the job may be attempted.
     */
    public $tries = 3;
    

    
    /**
     * Calculate the number of seconds to wait before retrying the job.
     */
    public function backoff(): array
    {
        return [60, 300, 900]; // 1 min, 5 min, 15 min
    }
    
    /**
     * Handle a job failure.
     */
    public function failed($exception)
    {
        $databaseKey = 'unknown';
        $filename = 'unknown';
        
        try {
            $databaseKey = $this->getDatabaseKey();
            $filename = $this->generateFilename();
        } catch (\Exception $e) {
            Log::error("Error in failed job handler: " . $e->getMessage());
        }
        
        Log::error("CSV export job failed for {$databaseKey}: " . $exception->getMessage(), [
            'user_id' => $this->user->id ?? null,
            'query_log_id' => $this->queryLogId ?? null,
            'exception' => $exception
        ]);
        
        // Update export download record
        if ($filename !== 'unknown') {
            ExportDownload::where('filename', $filename)->first()?->update([
                'status' => 'failed',
                'message' => $exception->getMessage()
            ]);
        }
        
        // Send failure notification email
        try {
            $messageContent = $this->initializeMessageContent($filename);
            $messageContent['export_failed'] = true;
            $messageContent['error'] = $exception->getMessage();
            
            $this->sendNotificationEmail($messageContent);
        } catch (\Exception $e) {
            Log::error("Failed to send failure notification: " . $e->getMessage());
        }
    }
    
    /**
     * Create a new job instance.
     */
    public function __construct($queryLogId, $user)
    {
        $this->queryLogId = $queryLogId;
        $this->user = $user;
        $this->currentBatchSize = $this->initialBatchSize;
        
        // Set the queue for this job (using onQueue method from Queueable trait)
        $this->onQueue('exports');
    }
    
    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Increase memory limit if possible
        ini_set('memory_limit', '2G');
        
        // Set maximum execution time to avoid timeout
        set_time_limit($this->maxExecutionTime);
        
        $filename = $this->generateFilename();
        $messageContent = $this->initializeMessageContent($filename);
        
        // Get request information
        $request = request();
        $ip = $request->ip();
        $userAgent = $request->userAgent();
        
        // Create an export download record
        $exportDownload = ExportDownload::create([
            'user_id' => $this->user->id,
            'filename' => $filename,
            'format' => 'csv',
            'ip_address' => $ip,
            'user_agent' => $userAgent,
            'database_key' => $this->getDatabaseKey(),
            'status' => 'processing'
        ]);
        
        // Associate with the query log
        $exportDownload->queryLogs()->attach($this->queryLogId);
        
        try {
            $startTime = microtime(true);
            $directory = $this->getStorageDirectory();
            
            // Make sure the directory exists
            Storage::makeDirectory($directory);
            
            $path = Storage::path("{$directory}/{$filename}");
            $handle = fopen($path, 'w');
            
            if (!$handle) {
                throw new Exception("Unable to open file for writing: {$path}");
            }
            
            // Write CSV headers
            fputcsv($handle, $this->getHeaders());
            
            // Get the query log record
            $queryLog = QueryLog::findOrFail($this->queryLogId);
            
            Log::info("Starting export for {$this->getDatabaseKey()}: extracting IDs from query");
            
            // Process records
            $totalExported = $this->processRecords($queryLog, $handle);
            
            fclose($handle);
            
            // Get file size and processing time
            $fileSize = Storage::size("{$directory}/{$filename}");
            $formattedFileSize = $this->formatBytes($fileSize);
            $processingTime = round(microtime(true) - $startTime, 2);
            
            // Update message content
            $messageContent['total_records'] = $totalExported;
            $messageContent['processing_time'] = $processingTime;
            $messageContent['file_size'] = $formattedFileSize;
            
            Log::info("{$this->getDatabaseKey()} export complete: {$totalExported} records exported in {$processingTime} seconds. File size: {$formattedFileSize}");
            
            // Update the export download record
            $exportDownload->update(['status' => 'completed']);
            
        } catch (Exception $e) {
            Log::error("{$this->getDatabaseKey()} export failed: " . $e->getMessage() . ' at line ' . $e->getLine() . ' in ' . $e->getFile());
            
            // Close file handle if it's open
            if (isset($handle) && is_resource($handle)) {
                fclose($handle);
            }
            
            // Update message content with error information
            $messageContent['export_failed'] = true;
            $messageContent['error'] = $e->getMessage();
            
            $exportDownload->update([
                'status' => 'failed',
                'message' => $e->getMessage()
            ]);
        }
        
        // Send notification email
        $this->sendNotificationEmail($messageContent);
    }
    
    /**
     * Process all records and write to CSV
     */
    protected function processRecords(QueryLog $queryLog, $handle): int
    {
        $totalExported = 0;
        $exportDate = Carbon::now()->format('Y-m-d H:i:s');
        
        // Use the optimized ID extraction method
        $idGenerator = $this->extractIds($queryLog);
        $idBatch = [];
        
        foreach ($idGenerator as $id) {
            $idBatch[] = $id;
            
            // When we have enough IDs for a batch, process them
            if (count($idBatch) >= $this->currentBatchSize) {
                $totalExported += $this->processIdBatch($idBatch, $handle, $exportDate);
                $this->optimizeBatchSize($idBatch);
                $idBatch = []; // Clear the batch
                
                // Free up memory
                gc_collect_cycles();
            }
        }
        
        // Process any remaining IDs
        if (!empty($idBatch)) {
            $totalExported += $this->processIdBatch($idBatch, $handle, $exportDate);
        }
        
        return $totalExported;
    }
    
    /**
     * Extract IDs from query log using optimized approach
     */
    protected function extractIds(QueryLog $queryLog)
    {
        // Use Query Builder instead of regex manipulation
        $baseQuery = $this->buildBaseQuery();
        
        // Apply filters from the original query (this is module-specific)
        $filteredQuery = $this->applyQueryFilters($baseQuery, $queryLog);
        
        // Use cursor for memory-efficient processing and yield IDs one by one
        $cursor = $filteredQuery
            ->select('id')
            ->orderBy('id') // Ensure consistent ordering and help with index usage
            ->cursor();
            
        foreach ($cursor as $record) {
            yield $record->id;
        }
    }
    
    /**
     * Process a batch of IDs and write records to CSV
     */
    protected function processIdBatch(array $idBatch, $handle, string $exportDate): int
    {
        try {
            Log::info("Processing batch of " . count($idBatch) . " records for {$this->getDatabaseKey()}");
            
            $exported = 0;
            $recordGenerator = $this->getRecordsBatch($idBatch);
            
            foreach ($recordGenerator as $record) {
                fputcsv($handle, $this->formatRecord($record, $exportDate));
                $exported++;
            }
            
            return $exported;
            
        } catch (Exception $e) {
            Log::error("Error processing batch for {$this->getDatabaseKey()}: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Optimize batch size based on performance
     */
    protected function optimizeBatchSize(array $lastBatch): void
    {
        $memoryUsage = memory_get_usage(true);
        $memoryLimit = $this->parseMemoryLimit(ini_get('memory_limit'));
        $memoryUsagePercent = ($memoryUsage / $memoryLimit) * 100;
        
        // If memory usage is low, increase batch size
        if ($memoryUsagePercent < 50 && $this->currentBatchSize < $this->maxBatchSize) {
            $this->currentBatchSize = min($this->currentBatchSize * 1.5, $this->maxBatchSize);
        }
        // If memory usage is high, decrease batch size
        elseif ($memoryUsagePercent > 80 && $this->currentBatchSize > $this->initialBatchSize) {
            $this->currentBatchSize = max($this->currentBatchSize * 0.7, $this->initialBatchSize);
        }
    }
    
    /**
     * Parse memory limit string to bytes
     */
    protected function parseMemoryLimit(string $memoryLimit): int
    {
        $memoryLimit = trim($memoryLimit);
        $last = strtolower($memoryLimit[strlen($memoryLimit)-1]);
        $value = (int) $memoryLimit;
        
        switch($last) {
            case 'g': $value *= 1024;
            case 'm': $value *= 1024;
            case 'k': $value *= 1024;
        }
        
        return $value;
    }
    
    /**
     * Generate filename for the export
     */
    protected function generateFilename(): string
    {
        return $this->getDatabaseKey() . '_export_uid_' . $this->user->id . '_' . Carbon::now()->format('YmdHis') . '.csv';
    }
    
    /**
     * Initialize message content for email notification
     */
    protected function initializeMessageContent(string $filename): array
    {
        return [
            'user' => $this->user->name ?? $this->user->email,
            'filename' => $filename,
            'download_link' => route('csv.download', ['filename' => $filename]),
            'total_records' => 0,
            'processing_time' => 0,
            'file_size' => '0 KB',
            'export_failed' => false
        ];
    }
    
    /**
     * Send notification email
     */
    protected function sendNotificationEmail(array $messageContent): void
    {
        try {
            $mailClass = $this->getMailClass();
            Mail::to($this->user->email)->queue(new $mailClass($messageContent));
        } catch (Exception $e) {
            Log::error("Failed to send email for {$this->getDatabaseKey()}: " . $e->getMessage());
            
            ExportDownload::where('filename', $messageContent['filename'])->first()?->update([
                'status' => 'failed',
                'message' => 'Export completed but email notification failed: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * Format bytes to human-readable file size
     */
    protected function formatBytes($bytes, $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= (1 << (10 * $pow));
        
        return round($bytes, $precision) . ' ' . $units[$pow];
    }
    
    // ===== ABSTRACT METHODS - Must be implemented by each module =====
    
    /**
     * Get the database key for this module
     */
    abstract protected function getDatabaseKey(): string;
    
    /**
     * Get the storage directory for exports
     */
    abstract protected function getStorageDirectory(): string;
    
    /**
     * Get CSV headers
     */
    abstract protected function getHeaders(): array;
    
    /**
     * Get the mail class for notifications
     */
    abstract protected function getMailClass(): string;
    
    /**
     * Build the base query for this module
     */
    abstract protected function buildBaseQuery();
    
    /**
     * Apply filters from the query log to the base query
     */
    abstract protected function applyQueryFilters($baseQuery, QueryLog $queryLog);
    
    /**
     * Get records for a batch of IDs
     */
    abstract protected function getRecordsBatch(array $idBatch);
    
    /**
     * Format a single record for CSV output
     */
    abstract protected function formatRecord($record, string $exportDate): array;
}