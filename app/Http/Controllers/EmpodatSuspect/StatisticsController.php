<?php

namespace App\Http\Controllers\EmpodatSuspect;

use App\Http\Controllers\Controller;
use App\Models\DatabaseEntity;
use App\Models\Statistic;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class StatisticsController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->checkModuleAccess();
    }

    /**
     * Check if user has access to the EmpodatSuspect module
     */
    private function checkModuleAccess(): void
    {
        $databaseEntity = DatabaseEntity::where('code', 'empodat_suspect')->first();

        if (! $databaseEntity) {
            abort(403, 'Module not found.');
        }

        // If module is public, allow access to everyone
        if ($databaseEntity->is_public === true) {
            return;
        }

        // Module is private - check if user is logged in
        if (! Auth::check()) {
            abort(403, 'You must be logged in to access this module.');
        }

        $user = Auth::user();

        // Always allow admin and super_admin
        if ($user->hasRole('admin') || $user->hasRole('super_admin')) {
            return;
        }

        // Check if user has the specific module role
        if ($user->hasRole('empodat_suspect')) {
            return;
        }

        // User doesn't have permission
        abort(403, 'You do not have permission to access this module.');
    }

    /**
     * Display statistics overview page
     */
    public function index()
    {
        // Get empodat_suspect database entity
        $empodatSuspectEntity = DatabaseEntity::where('code', 'empodat_suspect')->first();
        $allStats = [];

        if ($empodatSuspectEntity) {
            // Get all unique statistic keys for this entity
            $statisticKeys = Statistic::where('database_entity_id', $empodatSuspectEntity->id)
                ->distinct()
                ->pluck('key')
                ->toArray();

            foreach ($statisticKeys as $key) {
                $latestStat = Statistic::where('database_entity_id', $empodatSuspectEntity->id)
                    ->where('key', $key)
                    ->latest('created_at')
                    ->first();

                if ($latestStat) {
                    $allStats[$key] = $latestStat->meta_data;
                }
            }
        }

        $totalRecords = $empodatSuspectEntity->number_of_records ?? 0;

        return view('empodat_suspect.statistics.index', [
            'empodatSuspectEntity' => $empodatSuspectEntity,
            'allStats' => $allStats,
            'totalRecords' => $totalRecords,
        ]);
    }

    /**
     * Generate and store all statistics data
     */
    public function generateStatistics()
    {
        // Increase PHP timeout for long-running statistics generation
        set_time_limit(600); // 10 minutes

        // Set database timeout
        try {
            DB::statement('SET statement_timeout = 600000'); // 10 minutes in milliseconds
        } catch (\Exception $e) {
            // Ignore if not supported
        }

        // Get empodat_suspect database entity
        $empodatSuspectEntity = DatabaseEntity::where('code', 'empodat_suspect')->first();

        if (! $empodatSuspectEntity) {
            return back()->with('error', 'Empodat Suspect database entity not found.');
        }

        // 0. Records by concentration type (numeric vs non-numeric)
        // Use direct partition queries for better performance
        $numericCount = DB::table('empodat_suspect_main_numeric')->count();
        $nonNumericCount = DB::table('empodat_suspect_main_nonnumeric')->count();
        $totalCount = $numericCount + $nonNumericCount;

        Statistic::create([
            'database_entity_id' => $empodatSuspectEntity->id,
            'key' => 'empodat_suspect.records_by_concentration_type',
            'meta_data' => [
                'numeric_count' => $numericCount,
                'non_numeric_count' => $nonNumericCount,
                'total_count' => $totalCount,
                'numeric_percentage' => $totalCount > 0 ? round(($numericCount / $totalCount) * 100, 2) : 0,
                'non_numeric_percentage' => $totalCount > 0 ? round(($nonNumericCount / $totalCount) * 100, 2) : 0,
                'generated_at' => now()->toISOString(),
            ],
        ]);

        // 1. Total number of substances (with breakdown by concentration type)
        // Use direct partition queries for better performance
        $numericSubstances = DB::table('empodat_suspect_main_numeric')
            ->whereNotNull('substance_id')
            ->distinct()
            ->count('substance_id');

        $nonNumericSubstances = DB::table('empodat_suspect_main_nonnumeric')
            ->whereNotNull('substance_id')
            ->distinct()
            ->count('substance_id');

        $totalSubstances = DB::table('empodat_suspect_main')
            ->whereNotNull('substance_id')
            ->distinct()
            ->count('substance_id');

        Statistic::create([
            'database_entity_id' => $empodatSuspectEntity->id,
            'key' => 'empodat_suspect.total_substances',
            'meta_data' => [
                'count' => $totalSubstances,
                'numeric_count' => $numericSubstances,
                'non_numeric_count' => $nonNumericSubstances,
                'generated_at' => now()->toISOString(),
            ],
        ]);

        // 2. Number of substances per sample code (xlsx_name) - with numeric/non-numeric breakdown
        // Use direct partition queries for better performance
        $substancesBySampleCodeNumeric = DB::table('empodat_suspect_main_numeric as esm')
            ->join('empodat_suspect_xlsx_stations_mapping as mapping', 'esm.xlsx_station_mapping_id', '=', 'mapping.id')
            ->select(
                'mapping.xlsx_name as sample_code',
                DB::raw('COUNT(DISTINCT esm.substance_id) as substance_count')
            )
            ->whereNotNull('esm.substance_id')
            ->whereNotNull('mapping.xlsx_name')
            ->groupBy('mapping.xlsx_name')
            ->get()
            ->keyBy('sample_code');

        $substancesBySampleCodeNonNumeric = DB::table('empodat_suspect_main_nonnumeric as esm')
            ->join('empodat_suspect_xlsx_stations_mapping as mapping', 'esm.xlsx_station_mapping_id', '=', 'mapping.id')
            ->select(
                'mapping.xlsx_name as sample_code',
                DB::raw('COUNT(DISTINCT esm.substance_id) as substance_count')
            )
            ->whereNotNull('esm.substance_id')
            ->whereNotNull('mapping.xlsx_name')
            ->groupBy('mapping.xlsx_name')
            ->get()
            ->keyBy('sample_code');

        // Combine all sample codes from both partitions
        $allSampleCodes = collect($substancesBySampleCodeNumeric->keys())
            ->merge($substancesBySampleCodeNonNumeric->keys())
            ->unique();

        $substancesBySampleCode = [];
        foreach ($allSampleCodes as $sampleCode) {
            $numericCount = $substancesBySampleCodeNumeric[$sampleCode]->substance_count ?? 0;
            $nonNumericCount = $substancesBySampleCodeNonNumeric[$sampleCode]->substance_count ?? 0;
            $substancesBySampleCode[$sampleCode] = [
                'total' => $numericCount + $nonNumericCount,
                'numeric' => $numericCount,
                'non_numeric' => $nonNumericCount,
            ];
        }
        ksort($substancesBySampleCode);

        Statistic::create([
            'database_entity_id' => $empodatSuspectEntity->id,
            'key' => 'empodat_suspect.substances_by_sample_code',
            'meta_data' => [
                'data' => $substancesBySampleCode,
                'generated_at' => now()->toISOString(),
                'total_sample_codes' => count($substancesBySampleCode),
            ],
        ]);

        // 3. Number of records per sample code (xlsx_name) - with numeric/non-numeric breakdown
        // Use direct partition queries for better performance
        $recordsBySampleCodeNumeric = DB::table('empodat_suspect_main_numeric as esm')
            ->join('empodat_suspect_xlsx_stations_mapping as mapping', 'esm.xlsx_station_mapping_id', '=', 'mapping.id')
            ->select(
                'mapping.xlsx_name as sample_code',
                DB::raw('COUNT(*) as record_count')
            )
            ->whereNotNull('mapping.xlsx_name')
            ->groupBy('mapping.xlsx_name')
            ->get()
            ->keyBy('sample_code');

        $recordsBySampleCodeNonNumeric = DB::table('empodat_suspect_main_nonnumeric as esm')
            ->join('empodat_suspect_xlsx_stations_mapping as mapping', 'esm.xlsx_station_mapping_id', '=', 'mapping.id')
            ->select(
                'mapping.xlsx_name as sample_code',
                DB::raw('COUNT(*) as record_count')
            )
            ->whereNotNull('mapping.xlsx_name')
            ->groupBy('mapping.xlsx_name')
            ->get()
            ->keyBy('sample_code');

        // Combine all sample codes from both partitions
        $allRecordsSampleCodes = collect($recordsBySampleCodeNumeric->keys())
            ->merge($recordsBySampleCodeNonNumeric->keys())
            ->unique();

        $recordsBySampleCode = [];
        foreach ($allRecordsSampleCodes as $sampleCode) {
            $numericCount = $recordsBySampleCodeNumeric[$sampleCode]->record_count ?? 0;
            $nonNumericCount = $recordsBySampleCodeNonNumeric[$sampleCode]->record_count ?? 0;
            $recordsBySampleCode[$sampleCode] = [
                'total' => $numericCount + $nonNumericCount,
                'numeric' => $numericCount,
                'non_numeric' => $nonNumericCount,
            ];
        }
        ksort($recordsBySampleCode);

        Statistic::create([
            'database_entity_id' => $empodatSuspectEntity->id,
            'key' => 'empodat_suspect.records_by_sample_code',
            'meta_data' => [
                'data' => $recordsBySampleCode,
                'generated_at' => now()->toISOString(),
                'total_sample_codes' => count($recordsBySampleCode),
            ],
        ]);

        // 4. Number of substances per country - with numeric/non-numeric breakdown
        // Use direct partition queries for better performance
        $substancesByCountryNumeric = DB::table('empodat_suspect_main_numeric as esm')
            ->join('empodat_stations as es', 'esm.station_id', '=', 'es.id')
            ->join('list_countries as lc', 'es.country_id', '=', 'lc.id')
            ->select(
                'lc.name as country_name',
                'lc.code as country_code',
                DB::raw('COUNT(DISTINCT esm.substance_id) as substance_count')
            )
            ->whereNotNull('esm.substance_id')
            ->whereNotNull('es.country_id')
            ->groupBy('lc.name', 'lc.code')
            ->get()
            ->keyBy('country_name');

        $substancesByCountryNonNumeric = DB::table('empodat_suspect_main_nonnumeric as esm')
            ->join('empodat_stations as es', 'esm.station_id', '=', 'es.id')
            ->join('list_countries as lc', 'es.country_id', '=', 'lc.id')
            ->select(
                'lc.name as country_name',
                'lc.code as country_code',
                DB::raw('COUNT(DISTINCT esm.substance_id) as substance_count')
            )
            ->whereNotNull('esm.substance_id')
            ->whereNotNull('es.country_id')
            ->groupBy('lc.name', 'lc.code')
            ->get()
            ->keyBy('country_name');

        // Combine all countries from both partitions
        $allCountries = collect($substancesByCountryNumeric->keys())
            ->merge($substancesByCountryNonNumeric->keys())
            ->unique();

        $substancesByCountryData = [];
        foreach ($allCountries as $countryName) {
            $numericData = $substancesByCountryNumeric[$countryName] ?? null;
            $nonNumericData = $substancesByCountryNonNumeric[$countryName] ?? null;
            $code = $numericData->country_code ?? $nonNumericData->country_code ?? '';
            $numericCount = $numericData->substance_count ?? 0;
            $nonNumericCount = $nonNumericData->substance_count ?? 0;

            $substancesByCountryData[$countryName] = [
                'code' => $code,
                'count' => $numericCount + $nonNumericCount,
                'numeric' => $numericCount,
                'non_numeric' => $nonNumericCount,
            ];
        }
        ksort($substancesByCountryData);

        Statistic::create([
            'database_entity_id' => $empodatSuspectEntity->id,
            'key' => 'empodat_suspect.substances_by_country',
            'meta_data' => [
                'data' => $substancesByCountryData,
                'generated_at' => now()->toISOString(),
                'total_countries' => count($substancesByCountryData),
            ],
        ]);

        // 5. Number of records per country - with numeric/non-numeric breakdown
        // Use direct partition queries for better performance
        $recordsByCountryNumeric = DB::table('empodat_suspect_main_numeric as esm')
            ->join('empodat_stations as es', 'esm.station_id', '=', 'es.id')
            ->join('list_countries as lc', 'es.country_id', '=', 'lc.id')
            ->select(
                'lc.name as country_name',
                'lc.code as country_code',
                DB::raw('COUNT(*) as record_count')
            )
            ->whereNotNull('es.country_id')
            ->groupBy('lc.name', 'lc.code')
            ->get()
            ->keyBy('country_name');

        $recordsByCountryNonNumeric = DB::table('empodat_suspect_main_nonnumeric as esm')
            ->join('empodat_stations as es', 'esm.station_id', '=', 'es.id')
            ->join('list_countries as lc', 'es.country_id', '=', 'lc.id')
            ->select(
                'lc.name as country_name',
                'lc.code as country_code',
                DB::raw('COUNT(*) as record_count')
            )
            ->whereNotNull('es.country_id')
            ->groupBy('lc.name', 'lc.code')
            ->get()
            ->keyBy('country_name');

        // Combine all countries from both partitions
        $allRecordsCountries = collect($recordsByCountryNumeric->keys())
            ->merge($recordsByCountryNonNumeric->keys())
            ->unique();

        $recordsByCountryData = [];
        foreach ($allRecordsCountries as $countryName) {
            $numericData = $recordsByCountryNumeric[$countryName] ?? null;
            $nonNumericData = $recordsByCountryNonNumeric[$countryName] ?? null;
            $code = $numericData->country_code ?? $nonNumericData->country_code ?? '';
            $numericCount = $numericData->record_count ?? 0;
            $nonNumericCount = $nonNumericData->record_count ?? 0;

            $recordsByCountryData[$countryName] = [
                'code' => $code,
                'count' => $numericCount + $nonNumericCount,
                'numeric' => $numericCount,
                'non_numeric' => $nonNumericCount,
            ];
        }
        ksort($recordsByCountryData);

        Statistic::create([
            'database_entity_id' => $empodatSuspectEntity->id,
            'key' => 'empodat_suspect.records_by_country',
            'meta_data' => [
                'data' => $recordsByCountryData,
                'generated_at' => now()->toISOString(),
                'total_countries' => count($recordsByCountryData),
            ],
        ]);

        // 6. Number of records per confidence interval (ip_max ranges) - with numeric/non-numeric breakdown
        // Use direct partition queries for better performance
        $confidenceCaseStatement = "CASE
                    WHEN ip_max > 0.75 AND ip_max <= 1.00 THEN '1'
                    WHEN ip_max > 0.60 AND ip_max <= 0.75 THEN '2'
                    WHEN ip_max > 0.50 AND ip_max <= 0.60 THEN '3'
                    WHEN ip_max > 0.20 AND ip_max <= 0.50 THEN '4'
                    WHEN ip_max <= 0.20 THEN '5'
                    ELSE 'unknown'
                END";

        $recordsByConfidenceIntervalNumeric = DB::table('empodat_suspect_main_numeric')
            ->select(
                DB::raw("{$confidenceCaseStatement} as confidence_level"),
                DB::raw('COUNT(*) as record_count')
            )
            ->whereNotNull('ip_max')
            ->groupBy(DB::raw($confidenceCaseStatement))
            ->get()
            ->keyBy('confidence_level');

        $recordsByConfidenceIntervalNonNumeric = DB::table('empodat_suspect_main_nonnumeric')
            ->select(
                DB::raw("{$confidenceCaseStatement} as confidence_level"),
                DB::raw('COUNT(*) as record_count')
            )
            ->whereNotNull('ip_max')
            ->groupBy(DB::raw($confidenceCaseStatement))
            ->get()
            ->keyBy('confidence_level');

        // Combine all confidence levels from both partitions
        $allConfidenceLevels = collect($recordsByConfidenceIntervalNumeric->keys())
            ->merge($recordsByConfidenceIntervalNonNumeric->keys())
            ->unique();

        $confidenceLevelLabels = [
            '1' => 'IP_max > 0.75 AND <= 1.00',
            '2' => 'IP_max > 0.60 AND <= 0.75',
            '3' => 'IP_max > 0.50 AND <= 0.60',
            '4' => 'IP_max > 0.20 AND <= 0.50',
            '5' => 'IP_max <= 0.20',
            'unknown' => 'Unknown / NULL',
        ];

        $confidenceIntervalData = [];
        $totalWithIpMax = 0;
        $totalWithIpMaxNumeric = 0;
        $totalWithIpMaxNonNumeric = 0;

        foreach ($allConfidenceLevels as $level) {
            $label = $confidenceLevelLabels[$level] ?? $level;
            $numericCount = $recordsByConfidenceIntervalNumeric[$level]->record_count ?? 0;
            $nonNumericCount = $recordsByConfidenceIntervalNonNumeric[$level]->record_count ?? 0;
            $totalCount = $numericCount + $nonNumericCount;

            $confidenceIntervalData[$label] = [
                'level' => $level,
                'count' => $totalCount,
                'numeric' => $numericCount,
                'non_numeric' => $nonNumericCount,
            ];
            $totalWithIpMax += $totalCount;
            $totalWithIpMaxNumeric += $numericCount;
            $totalWithIpMaxNonNumeric += $nonNumericCount;
        }

        // Also count records with NULL ip_max (separated by concentration type)
        // Use direct partition queries for better performance
        $nullIpMaxNumericCount = DB::table('empodat_suspect_main_numeric')
            ->whereNull('ip_max')
            ->count();
        $nullIpMaxNonNumericCount = DB::table('empodat_suspect_main_nonnumeric')
            ->whereNull('ip_max')
            ->count();
        $nullIpMaxCount = $nullIpMaxNumericCount + $nullIpMaxNonNumericCount;

        if ($nullIpMaxCount > 0) {
            $confidenceIntervalData['No IP_max value'] = [
                'level' => 'null',
                'count' => $nullIpMaxCount,
                'numeric' => $nullIpMaxNumericCount,
                'non_numeric' => $nullIpMaxNonNumericCount,
            ];
        }

        Statistic::create([
            'database_entity_id' => $empodatSuspectEntity->id,
            'key' => 'empodat_suspect.records_by_confidence_interval',
            'meta_data' => [
                'data' => $confidenceIntervalData,
                'generated_at' => now()->toISOString(),
                'total_with_ip_max' => $totalWithIpMax,
                'total_with_ip_max_numeric' => $totalWithIpMaxNumeric,
                'total_with_ip_max_non_numeric' => $totalWithIpMaxNonNumeric,
                'total_without_ip_max' => $nullIpMaxCount,
                'total_without_ip_max_numeric' => $nullIpMaxNumericCount,
                'total_without_ip_max_non_numeric' => $nullIpMaxNonNumericCount,
            ],
        ]);

        // Update the database entity with total record count and last update timestamp
        $empodatSuspectEntity->update([
            'number_of_records' => $totalCount,
            'last_update' => now(),
        ]);

        return back()->with('success', 'All statistics generated and stored successfully.');
    }

    /**
     * Display substances by sample code statistics
     */
    public function substancesBySampleCode()
    {
        $empodatSuspectEntity = DatabaseEntity::where('code', 'empodat_suspect')->first();

        if (! $empodatSuspectEntity) {
            return back()->with('error', 'Empodat Suspect database entity not found.');
        }

        $statisticsRecord = Statistic::where('database_entity_id', $empodatSuspectEntity->id)
            ->where('key', 'empodat_suspect.substances_by_sample_code')
            ->latest('created_at')
            ->first();

        if (! $statisticsRecord) {
            return view('empodat_suspect.statistics.substances_by_sample_code', [
                'data' => [],
                'message' => 'No statistics available. Please generate statistics first.',
                'generatedAt' => null,
            ]);
        }

        return view('empodat_suspect.statistics.substances_by_sample_code', [
            'data' => $statisticsRecord->meta_data['data'] ?? [],
            'totalSampleCodes' => $statisticsRecord->meta_data['total_sample_codes'] ?? 0,
            'generatedAt' => $statisticsRecord->meta_data['generated_at'] ?? null,
            'message' => null,
        ]);
    }

    /**
     * Display records by sample code statistics
     */
    public function recordsBySampleCode()
    {
        $empodatSuspectEntity = DatabaseEntity::where('code', 'empodat_suspect')->first();

        if (! $empodatSuspectEntity) {
            return back()->with('error', 'Empodat Suspect database entity not found.');
        }

        $statisticsRecord = Statistic::where('database_entity_id', $empodatSuspectEntity->id)
            ->where('key', 'empodat_suspect.records_by_sample_code')
            ->latest('created_at')
            ->first();

        if (! $statisticsRecord) {
            return view('empodat_suspect.statistics.records_by_sample_code', [
                'data' => [],
                'message' => 'No statistics available. Please generate statistics first.',
                'generatedAt' => null,
            ]);
        }

        return view('empodat_suspect.statistics.records_by_sample_code', [
            'data' => $statisticsRecord->meta_data['data'] ?? [],
            'totalSampleCodes' => $statisticsRecord->meta_data['total_sample_codes'] ?? 0,
            'generatedAt' => $statisticsRecord->meta_data['generated_at'] ?? null,
            'message' => null,
        ]);
    }

    /**
     * Display substances by country statistics
     */
    public function substancesByCountry()
    {
        $empodatSuspectEntity = DatabaseEntity::where('code', 'empodat_suspect')->first();

        if (! $empodatSuspectEntity) {
            return back()->with('error', 'Empodat Suspect database entity not found.');
        }

        $statisticsRecord = Statistic::where('database_entity_id', $empodatSuspectEntity->id)
            ->where('key', 'empodat_suspect.substances_by_country')
            ->latest('created_at')
            ->first();

        if (! $statisticsRecord) {
            return view('empodat_suspect.statistics.substances_by_country', [
                'data' => [],
                'message' => 'No statistics available. Please generate statistics first.',
                'generatedAt' => null,
            ]);
        }

        return view('empodat_suspect.statistics.substances_by_country', [
            'data' => $statisticsRecord->meta_data['data'] ?? [],
            'totalCountries' => $statisticsRecord->meta_data['total_countries'] ?? 0,
            'generatedAt' => $statisticsRecord->meta_data['generated_at'] ?? null,
            'message' => null,
        ]);
    }

    /**
     * Display records by country statistics
     */
    public function recordsByCountry()
    {
        $empodatSuspectEntity = DatabaseEntity::where('code', 'empodat_suspect')->first();

        if (! $empodatSuspectEntity) {
            return back()->with('error', 'Empodat Suspect database entity not found.');
        }

        $statisticsRecord = Statistic::where('database_entity_id', $empodatSuspectEntity->id)
            ->where('key', 'empodat_suspect.records_by_country')
            ->latest('created_at')
            ->first();

        if (! $statisticsRecord) {
            return view('empodat_suspect.statistics.records_by_country', [
                'data' => [],
                'message' => 'No statistics available. Please generate statistics first.',
                'generatedAt' => null,
            ]);
        }

        return view('empodat_suspect.statistics.records_by_country', [
            'data' => $statisticsRecord->meta_data['data'] ?? [],
            'totalCountries' => $statisticsRecord->meta_data['total_countries'] ?? 0,
            'generatedAt' => $statisticsRecord->meta_data['generated_at'] ?? null,
            'message' => null,
        ]);
    }

    /**
     * Display records by confidence interval statistics
     */
    public function recordsByConfidenceInterval()
    {
        $empodatSuspectEntity = DatabaseEntity::where('code', 'empodat_suspect')->first();

        if (! $empodatSuspectEntity) {
            return back()->with('error', 'Empodat Suspect database entity not found.');
        }

        $statisticsRecord = Statistic::where('database_entity_id', $empodatSuspectEntity->id)
            ->where('key', 'empodat_suspect.records_by_confidence_interval')
            ->latest('created_at')
            ->first();

        if (! $statisticsRecord) {
            return view('empodat_suspect.statistics.records_by_confidence_interval', [
                'data' => [],
                'message' => 'No statistics available. Please generate statistics first.',
                'generatedAt' => null,
            ]);
        }

        return view('empodat_suspect.statistics.records_by_confidence_interval', [
            'data' => $statisticsRecord->meta_data['data'] ?? [],
            'totalWithIpMax' => $statisticsRecord->meta_data['total_with_ip_max'] ?? 0,
            'totalWithoutIpMax' => $statisticsRecord->meta_data['total_without_ip_max'] ?? 0,
            'generatedAt' => $statisticsRecord->meta_data['generated_at'] ?? null,
            'message' => null,
        ]);
    }
}
