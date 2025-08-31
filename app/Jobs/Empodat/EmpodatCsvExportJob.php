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
     * Optimized for large datasets - use larger initial batch sizes
     */
    protected $initialBatchSize = 750;
    protected $maxBatchSize = 2500;
    
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
     * Apply filters from the query log to the base query
     * 
     * This method reconstructs the query filters from the logged query
     * without using regex manipulation
     */
    protected function applyQueryFilters($baseQuery, QueryLog $queryLog)
    {
        // Parse the content JSON to get the original request parameters
        $content = json_decode($queryLog->content, true);
        $request = $content['request'] ?? [];
        
        // Handle ID range filters (most common case)
        if (!empty($request['id_from']) || !empty($request['id_to'])) {
            if (!empty($request['id_from']) && !empty($request['id_to'])) {
                $baseQuery->whereBetween('id', [$request['id_from'], $request['id_to']]);
            } elseif (!empty($request['id_from'])) {
                $baseQuery->where('id', '>=', $request['id_from']);
            } elseif (!empty($request['id_to'])) {
                $baseQuery->where('id', '<=', $request['id_to']);
            }
        }
        
        // Apply the same filters that were used in the original search
        if (!empty($request['countrySearch'])) {
            $baseQuery->whereHas('station.country', function ($subQuery) use ($request) {
                $subQuery->whereIn('id', $request['countrySearch']);
            });
        }
        
        if (!empty($request['matrixSearch'])) {
            $baseQuery->whereIn('matrix_id', $request['matrixSearch']);
        }
        
        if (!empty($request['substanceSearch'])) {
            $baseQuery->whereIn('substance_id', $request['substanceSearch']);
        }
        
        if (!empty($request['normanRelevantOnly']) && $request['normanRelevantOnly']) {
            $baseQuery->whereHas('substance', function ($subQuery) {
                $subQuery->where('relevant_to_norman', 1);
            });
        }
        
        if (!empty($request['concentrationIndicatorSearch'])) {
            $baseQuery->whereIn('concentration_indicator_id', $request['concentrationIndicatorSearch']);
        }
        
        if (!empty($request['year_from'])) {
            $baseQuery->where('sampling_date_year', '>=', $request['year_from']);
        }
        
        if (!empty($request['year_to'])) {
            $baseQuery->where('sampling_date_year', '<=', $request['year_to']);
        }
        
        if (!empty($request['categorySearch'])) {
            $baseQuery->whereHas('substance.categories', function ($subQuery) use ($request) {
                $subQuery->whereIn('susdat_categories.id', $request['categorySearch']);
            });
        }
        
        if (!empty($request['sourceSearch'])) {
            $baseQuery->whereHas('substance.sources', function ($subQuery) use ($request) {
                $subQuery->whereIn('sle_sources.id', $request['sourceSearch']);
            });
        }
        
        if (!empty($request['typeDataSourceSearch']) || !empty($request['laboratorySearch']) || !empty($request['organisationSearch'])) {
            $baseQuery->whereHas('dataSource', function ($subQuery) use ($request) {
                if (!empty($request['typeDataSourceSearch'])) {
                    $subQuery->whereIn('type_data_source_id', $request['typeDataSourceSearch']);
                }
                if (!empty($request['laboratorySearch'])) {
                    $subQuery->whereIn('laboratory1_id', $request['laboratorySearch']);
                }
                if (!empty($request['organisationSearch'])) {
                    $subQuery->whereIn('organisation_id', $request['organisationSearch']);
                }
            });
        }
        
        if (!empty($request['analyticalMethodSearch'])) {
            $baseQuery->whereHas('analyticalMethod', function ($subQuery) use ($request) {
                $subQuery->whereIn('analytical_method_id', $request['analyticalMethodSearch']);
            });
        }
        
        if (!empty($request['qualityRatingSearch'])) {
            $baseQuery->whereHas('analyticalMethod', function ($subQuery) use ($request) {
                $subQuery->where(function ($ratingQuery) use ($request) {
                    foreach ($request['qualityRatingSearch'] as $rating) {
                        $ratingQuery->orWhere(function ($individualRating) use ($rating) {
                            $individualRating->where('rating', '>=', $rating['min_rating'])
                                ->where('rating', '<', $rating['max_rating']);
                        });
                    }
                });
            });
        }
        
        if (!empty($request['fileSearch'])) {
            $baseQuery->whereHas('files', function ($subQuery) use ($request) {
                $subQuery->whereIn('files.id', $request['fileSearch']);
            });
        }
        
        return $baseQuery;
    }
    
    /**
     * Get records for a batch of IDs with all necessary relationships
     * Optimized for large datasets with proper indexing hints
     */
    protected function getRecordsBatch(array $idBatch)
    {
        // For very large batches, use ordered retrieval to help with database optimization
        $orderedIds = array_values($idBatch);
        sort($orderedIds);
        
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
            ->orderBy('empodat_main.id') // Help database optimize with ordered retrieval
            ->cursor();
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