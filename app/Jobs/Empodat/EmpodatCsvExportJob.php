<?php

namespace App\Jobs\Empodat;

use App\Jobs\AbstractCsvExportJob;
use App\Models\Backend\QueryLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Mail\Empodat\CsvExportReady;
use App\Models\Empodat\EmpodatMain;

class EmpodatCsvExportJob extends AbstractCsvExportJob
{
    /**
     * The filename for the CSV export
     */
    protected $filename;
    
    /**
     * Optimized for development - smaller batch sizes for faster processing
     */
    protected $initialBatchSize = 50;
    protected $maxBatchSize = 500;
    
    /**
     * Extended timeout for large datasets
     */
    protected $maxExecutionTime = 3600; // 1 hour for very large exports
    
    /**
     * Get the database key for this module
     */
    protected function getDatabaseKey(): string
    {
        return 'empodat';
    }
    
    /**
     * Get the storage directory for exports
     */
    protected function getStorageDirectory(): string
    {
        return 'exports/empodat';
    }
    
    /**
     * Get CSV headers
     */
    protected function getHeaders(): array
    {
        return [
            'ID', 
            'DCT Analysis ID', 
            'Station Name',
            'Country',
            'Country Code',
            'Matrix',
            'Concentration Unit',
            'Substance',
            'CAS Number',
            'Sampling Year',
            'Concentration Value',
            'Latitude',
            'Longitude',
            'Export Date'
        ];
    }
    
    /**
     * Get the mail class for notifications
     */
    protected function getMailClass(): string
    {
        return CsvExportReady::class;
    }
    
    /**
     * Build the base query for this module
     */
    protected function buildBaseQuery()
    {
        return EmpodatMain::query();
    }
    
    /**
     * 
     * Apply filters from the query log to the base query using optimized JOINs
     * 
     * This method reconstructs the query filters using the same optimized approach
     * as the main search functionality
     */
    protected function applyQueryFilters($baseQuery, QueryLog $queryLog)
    {
        // Parse the content JSON to get the original request parameters
        $content = json_decode($queryLog->content, true);
        
        if (!is_array($content)) {
            Log::warning("Invalid query log content for ID {$queryLog->id}");
            return $baseQuery;
        }
        
        $request = $content['request'] ?? [];
        
        if (!is_array($request)) {
            Log::warning("Invalid request data in query log ID {$queryLog->id}");
            return $baseQuery;
        }
        
        // Handle ID range filters (most common case)
        if (!empty($request['id_from']) || !empty($request['id_to'])) {
            if (!empty($request['id_from']) && !empty($request['id_to'])) {
                $baseQuery->whereBetween('empodat_main.id', [$request['id_from'], $request['id_to']]);
            } elseif (!empty($request['id_from'])) {
                $baseQuery->where('empodat_main.id', '>=', $request['id_from']);
            } elseif (!empty($request['id_to'])) {
                $baseQuery->where('empodat_main.id', '<=', $request['id_to']);
            }
        }
        
        // Use optimized scope methods with JOINs instead of whereHas
        if (!empty($request['countrySearch']) && is_array($request['countrySearch'])) {
            $baseQuery->byCountries($request['countrySearch']);
        }
        
        if (!empty($request['matrixSearch']) && is_array($request['matrixSearch'])) {
            $baseQuery->byMatrices($request['matrixSearch']);
        }
        
        if (!empty($request['substances']) && is_array($request['substances'])) {
            $baseQuery->bySubstances($request['substances']);
        }
        
        if (!empty($request['normanRelevantOnly']) && $request['normanRelevantOnly']) {
            $baseQuery->normanRelevant();
        }
        
        if (!empty($request['concentrationIndicatorSearch']) && is_array($request['concentrationIndicatorSearch'])) {
            $baseQuery->byConcentrationIndicators($request['concentrationIndicatorSearch']);
        }
        
        if (!empty($request['year_from']) || !empty($request['year_to'])) {
            $baseQuery->byYearRange($request['year_from'], $request['year_to']);
        }
        
        if (!empty($request['categoriesSearch']) && is_array($request['categoriesSearch'])) {
            $baseQuery->byCategories($request['categoriesSearch']);
        }
        
        if (!empty($request['sourceSearch']) && is_array($request['sourceSearch'])) {
            $baseQuery->bySources($request['sourceSearch']);
        }
        
        if ((!empty($request['typeDataSourcesSearch']) && is_array($request['typeDataSourcesSearch'])) || 
            (!empty($request['dataSourceLaboratorySearch']) && is_array($request['dataSourceLaboratorySearch'])) || 
            (!empty($request['dataSourceOrganisationSearch']) && is_array($request['dataSourceOrganisationSearch']))) {
            $baseQuery->byDataSourceFilters(
                $request['typeDataSourcesSearch'] ?? [],
                $request['dataSourceLaboratorySearch'] ?? [],
                $request['dataSourceOrganisationSearch'] ?? []
            );
        }
        
        if (!empty($request['analyticalMethodSearch']) && is_array($request['analyticalMethodSearch'])) {
            $baseQuery->byAnalyticalMethods($request['analyticalMethodSearch']);
        }
        
        if (!empty($request['qualityAnalyticalMethodsSearch']) && is_array($request['qualityAnalyticalMethodsSearch'])) {
            // Get the quality ratings collection like in the main search
            $ratings = \App\Models\List\QualityEmpodatAnalyticalMethods::whereIn('id', $request['qualityAnalyticalMethodsSearch'])->get();
            $baseQuery->byQualityRatings($ratings);
        }
        
        if (!empty($request['fileSearch']) && is_array($request['fileSearch'])) {
            $baseQuery->byFiles($request['fileSearch']);
        }
        
        return $baseQuery;
    }
    
    /**
     * Override ID extraction to handle column ambiguity with JOINs
     */
    protected function extractIds(QueryLog $queryLog)
    {
        $baseQuery = $this->buildBaseQuery();
        $filteredQuery = $this->applyQueryFilters($baseQuery, $queryLog);
        
        // Explicitly select empodat_main.id to avoid ambiguity
        $filteredQuery->select('empodat_main.id')
            ->orderBy('empodat_main.id');
        
        // Use chunking for memory efficiency
        $ids = [];
        $filteredQuery->chunk(1000, function ($records) use (&$ids) {
            foreach ($records as $record) {
                $ids[] = $record->id;
            }
        });
        
        // Return generator for memory efficiency
        foreach ($ids as $id) {
            yield $id;
        }
    }
    
    /**
     * Override chunked ID extraction to handle column ambiguity  
     */
    protected function extractIdsChunked(QueryLog $queryLog)
    {
        $baseQuery = $this->buildBaseQuery();
        $filteredQuery = $this->applyQueryFilters($baseQuery, $queryLog);
        
        // Explicitly select empodat_main.id to avoid ambiguity
        $ids = $filteredQuery->select('empodat_main.id')
                           ->orderBy('empodat_main.id')
                           ->pluck('empodat_main.id')
                           ->toArray();
        
        foreach ($ids as $id) {
            yield $id;
        }
    }
    
    /**
     * Get records for a batch of IDs with all necessary relationships
     * Optimized to avoid JOIN conflicts with filtered queries
     */
    protected function getRecordsBatch(array $idBatch)
    {
        // For development, use smaller batches with aggressive optimization
        $orderedIds = array_values($idBatch);
        sort($orderedIds);
        
        // Log batch processing for debugging
        Log::info("Processing Empodat batch", [
            'batch_size' => count($orderedIds),
            'first_id' => $orderedIds[0] ?? null,
            'last_id' => end($orderedIds) ?: null
        ]);
        
        // Use a separate optimized query for data retrieval to avoid JOIN conflicts
        // This approach ensures we get all the data we need without interfering with filtering JOINs
        return DB::table('empodat_main')
            ->select(
                'empodat_main.id',
                'empodat_main.dct_analysis_id', 
                'empodat_main.sampling_date_year',
                'empodat_main.concentration_value',
                'empodat_stations.name as station_name',
                'list_countries.name as country_name',
                'list_countries.code as country_code',
                'list_matrices.name as matrix_name',
                'list_matrices.unit as concentration_unit',
                'susdat_substances.name as substance_name',
                'susdat_substances.cas_number',
                'empodat_stations.latitude',
                'empodat_stations.longitude'
            )
            ->leftJoin('susdat_substances', 'empodat_main.substance_id', '=', 'susdat_substances.id')
            ->leftJoin('list_matrices', 'empodat_main.matrix_id', '=', 'list_matrices.id')
            ->leftJoin('empodat_stations', 'empodat_main.station_id', '=', 'empodat_stations.id')
            ->leftJoin('list_countries', 'empodat_stations.country_id', '=', 'list_countries.id')
            ->whereIn('empodat_main.id', $orderedIds)
            ->orderBy('empodat_main.id')
            ->cursor(); // Use cursor for memory efficiency with larger datasets
    }
    
    /**
     * Override batch size optimization for Empodat's specific needs
     */
    protected function optimizeBatchSize(array $lastBatch): void
    {
        parent::optimizeBatchSize($lastBatch);
        
        // Additional Empodat-specific optimization
        // If we're processing more than 50k records, be more conservative with memory
        $memoryUsage = memory_get_usage(true);
        
        // Log memory usage every 10 batches for monitoring
        static $batchCount = 0;
        $batchCount++;
        
        if ($batchCount % 10 === 0) {
            Log::info("Empodat export: Batch {$batchCount}, Memory usage: " . $this->formatBytes($memoryUsage) . ", Current batch size: {$this->currentBatchSize}");
        }
    }
    
    /**
     * Format a single record for CSV output
     */
    protected function formatRecord($record, string $exportDate): array
    {
        return [
            $record->id ?? 'N/A',
            $record->dct_analysis_id ?? 'N/A',
            $record->station_name ?? 'N/A',
            $record->country_name ?? 'N/A',
            $record->country_code ?? 'N/A',
            $record->matrix_name ?? 'N/A',
            $record->concentration_unit ?? 'N/A',
            $record->substance_name ?? 'N/A',
            $record->cas_number ?? 'N/A',
            $record->sampling_date_year ?? 'N/A',
            $record->concentration_value ?? 'N/A',
            $record->latitude ?? 'N/A',
            $record->longitude ?? 'N/A',
            $exportDate
        ];
    }
}