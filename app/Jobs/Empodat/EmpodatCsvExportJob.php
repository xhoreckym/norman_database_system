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
     * Optimized batch sizes for efficient processing of 95M rows
     */
    protected $initialBatchSize = 1000;
    protected $maxBatchSize = 5000;

    /**
     * Extended timeout for large datasets (95M rows)
     */
    protected $maxExecutionTime = 7200; // 2 hours for very large exports

    /**
     * Job timeout
     */
    public $timeout = 10800; // 3 hours
    
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
            // empodat_main fields
            'ID',
            'Station ID',
            'Station Name',
            'Country ID',
            'Country',
            'Country Code',
            'File ID',
            'Matrix ID',
            'Matrix',
            'Concentration Unit',
            'Substance ID',
            'Substance',
            'CAS Number',
            'Sampling Year',
            'Concentration Indicator ID',
            'Concentration Value',
            'Method ID',
            'Data Source ID',
            'Latitude',
            'Longitude',

            // empodat_minor fields
            'DPC ID',
            'Altitude',
            'Matrix Other',
            'Compound',
            'DCOD ID',
            'Unit Extra',
            'Tier',
            'Sampling Technique',
            'Sampling Date',
            'Sampling Date T',
            'Sampling Date1 Y',
            'Sampling Date1 M',
            'Sampling Date1 D',
            'Sampling Date1 T',
            'Sampling Date1',
            'Analysis Date Y',
            'Analysis Date M',
            'Analysis Date D',
            'Sampling Duration Day',
            'Sampling Duration Hour',
            'Description',
            'Remark',
            'Remark Add',
            'Show Date',
            'DTOD ID',
            'DTOD Other',
            'Agg Uncertainty',
            'DMM ID',
            'Agg Max',
            'Agg Min',
            'Agg Number',
            'Agg Deviation',
            'DTL ID',
            'DTL Other',
            'DST ID',
            'DST Other',
            'DTOS ID',
            'DPLU ID',
            'No Export',
            'List ID',

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
        $orderedIds = array_values($idBatch);
        sort($orderedIds);

        // Use a separate optimized query for data retrieval to avoid JOIN conflicts
        // This approach ensures we get all the data we need without interfering with filtering JOINs
        return DB::table('empodat_main')
            ->select(
                // empodat_main fields
                'empodat_main.id',
                'empodat_main.station_id',
                'empodat_stations.name as station_name',
                'empodat_main.country_id',
                'list_countries.name as country_name',
                'list_countries.code as country_code',
                'empodat_main.file_id',
                'empodat_main.matrix_id',
                'list_matrices.name as matrix_name',
                'list_matrices.unit as concentration_unit',
                'empodat_main.substance_id',
                'susdat_substances.name as substance_name',
                'susdat_substances.cas_number',
                'empodat_main.sampling_date_year',
                'empodat_main.concentration_indicator_id',
                'empodat_main.concentration_value',
                'empodat_main.method_id',
                'empodat_main.data_source_id',
                'empodat_stations.latitude',
                'empodat_stations.longitude',

                // empodat_minor fields
                'empodat_minor.dpc_id',
                'empodat_minor.altitude',
                'empodat_minor.matrix_other',
                'empodat_minor.compound',
                'empodat_minor.dcod_id',
                'empodat_minor.unit_extra',
                'empodat_minor.tier',
                'empodat_minor.sampling_technique',
                'empodat_minor.sampling_date',
                'empodat_minor.sampling_date_t',
                'empodat_minor.sampling_date1_y',
                'empodat_minor.sampling_date1_m',
                'empodat_minor.sampling_date1_d',
                'empodat_minor.sampling_date1_t',
                'empodat_minor.sampling_date1',
                'empodat_minor.analysis_date_y',
                'empodat_minor.analysis_date_m',
                'empodat_minor.analysis_date_d',
                'empodat_minor.sampling_duration_day',
                'empodat_minor.sampling_duration_hour',
                'empodat_minor.description',
                'empodat_minor.remark',
                'empodat_minor.remark_add',
                'empodat_minor.show_date',
                'empodat_minor.dtod_id',
                'empodat_minor.dtod_other',
                'empodat_minor.agg_uncertainty',
                'empodat_minor.dmm_id',
                'empodat_minor.agg_max',
                'empodat_minor.agg_min',
                'empodat_minor.agg_number',
                'empodat_minor.agg_deviation',
                'empodat_minor.dtl_id',
                'empodat_minor.dtl_other',
                'empodat_minor.dst_id',
                'empodat_minor.dst_other',
                'empodat_minor.dtos_id',
                'empodat_minor.dplu_id',
                'empodat_minor.noexport',
                'empodat_minor.list_id'
            )
            ->leftJoin('empodat_minor', 'empodat_main.id', '=', 'empodat_minor.id')
            ->leftJoin('empodat_stations', 'empodat_main.station_id', '=', 'empodat_stations.id')
            ->leftJoin('list_countries', 'empodat_main.country_id', '=', 'list_countries.id')
            ->leftJoin('list_matrices', 'empodat_main.matrix_id', '=', 'list_matrices.id')
            ->leftJoin('susdat_substances', 'empodat_main.substance_id', '=', 'susdat_substances.id')
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
    }
    
    /**
     * Format a single record for CSV output
     */
    protected function formatRecord($record, string $exportDate): array
    {
        return [
            // empodat_main fields
            $record->id ?? '',
            $record->station_id ?? '',
            $record->station_name ?? '',
            $record->country_id ?? '',
            $record->country_name ?? '',
            $record->country_code ?? '',
            $record->file_id ?? '',
            $record->matrix_id ?? '',
            $record->matrix_name ?? '',
            $record->concentration_unit ?? '',
            $record->substance_id ?? '',
            $record->substance_name ?? '',
            $record->cas_number ?? '',
            $record->sampling_date_year ?? '',
            $record->concentration_indicator_id ?? '',
            $record->concentration_value ?? '',
            $record->method_id ?? '',
            $record->data_source_id ?? '',
            $record->latitude ?? '',
            $record->longitude ?? '',

            // empodat_minor fields
            $record->dpc_id ?? '',
            $record->altitude ?? '',
            $record->matrix_other ?? '',
            $record->compound ?? '',
            $record->dcod_id ?? '',
            $record->unit_extra ?? '',
            $record->tier ?? '',
            $record->sampling_technique ?? '',
            $record->sampling_date ?? '',
            $record->sampling_date_t ?? '',
            $record->sampling_date1_y ?? '',
            $record->sampling_date1_m ?? '',
            $record->sampling_date1_d ?? '',
            $record->sampling_date1_t ?? '',
            $record->sampling_date1 ?? '',
            $record->analysis_date_y ?? '',
            $record->analysis_date_m ?? '',
            $record->analysis_date_d ?? '',
            $record->sampling_duration_day ?? '',
            $record->sampling_duration_hour ?? '',
            $record->description ?? '',
            $record->remark ?? '',
            $record->remark_add ?? '',
            $record->show_date ?? '',
            $record->dtod_id ?? '',
            $record->dtod_other ?? '',
            $record->agg_uncertainty ?? '',
            $record->dmm_id ?? '',
            $record->agg_max ?? '',
            $record->agg_min ?? '',
            $record->agg_number ?? '',
            $record->agg_deviation ?? '',
            $record->dtl_id ?? '',
            $record->dtl_other ?? '',
            $record->dst_id ?? '',
            $record->dst_other ?? '',
            $record->dtos_id ?? '',
            $record->dplu_id ?? '',
            $record->noexport ?? '',
            $record->list_id ?? '',

            $exportDate
        ];
    }
}