<?php

namespace App\Http\Controllers\EmpodatSuspect;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\EmpodatSuspect\EmpodatSuspectMain;
use App\Models\Statistic;
use App\Models\DatabaseEntity;
use Illuminate\Support\Facades\Auth;

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

        if (!$databaseEntity) {
            abort(403, 'Module not found.');
        }

        // If module is public, allow access to everyone
        if ($databaseEntity->is_public === true) {
            return;
        }

        // Module is private - check if user is logged in
        if (!Auth::check()) {
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
        // Get empodat_suspect database entity
        $empodatSuspectEntity = DatabaseEntity::where('code', 'empodat_suspect')->first();

        if (!$empodatSuspectEntity) {
            return back()->with('error', 'Empodat Suspect database entity not found.');
        }

        // 1. Total number of substances
        $totalSubstances = EmpodatSuspectMain::distinct('substance_id')
            ->whereNotNull('substance_id')
            ->count('substance_id');

        Statistic::create([
            'database_entity_id' => $empodatSuspectEntity->id,
            'key' => 'empodat_suspect.total_substances',
            'meta_data' => [
                'count' => $totalSubstances,
                'generated_at' => now()->toISOString(),
            ]
        ]);

        // 2. Number of substances per sample code (xlsx_name)
        $substancesBySampleCode = DB::table('empodat_suspect_main as esm')
            ->join('empodat_suspect_xlsx_stations_mapping as mapping', 'esm.xlsx_station_mapping_id', '=', 'mapping.id')
            ->select(
                'mapping.xlsx_name as sample_code',
                DB::raw('COUNT(DISTINCT esm.substance_id) as substance_count')
            )
            ->whereNotNull('esm.substance_id')
            ->whereNotNull('mapping.xlsx_name')
            ->groupBy('mapping.xlsx_name')
            ->orderBy('mapping.xlsx_name')
            ->get()
            ->pluck('substance_count', 'sample_code')
            ->toArray();

        Statistic::create([
            'database_entity_id' => $empodatSuspectEntity->id,
            'key' => 'empodat_suspect.substances_by_sample_code',
            'meta_data' => [
                'data' => $substancesBySampleCode,
                'generated_at' => now()->toISOString(),
                'total_sample_codes' => count($substancesBySampleCode),
            ]
        ]);

        // 3. Number of records per sample code (xlsx_name)
        $recordsBySampleCode = DB::table('empodat_suspect_main as esm')
            ->join('empodat_suspect_xlsx_stations_mapping as mapping', 'esm.xlsx_station_mapping_id', '=', 'mapping.id')
            ->select(
                'mapping.xlsx_name as sample_code',
                DB::raw('COUNT(*) as record_count')
            )
            ->whereNotNull('mapping.xlsx_name')
            ->groupBy('mapping.xlsx_name')
            ->orderBy('mapping.xlsx_name')
            ->get()
            ->pluck('record_count', 'sample_code')
            ->toArray();

        Statistic::create([
            'database_entity_id' => $empodatSuspectEntity->id,
            'key' => 'empodat_suspect.records_by_sample_code',
            'meta_data' => [
                'data' => $recordsBySampleCode,
                'generated_at' => now()->toISOString(),
                'total_sample_codes' => count($recordsBySampleCode),
            ]
        ]);

        // 4. Number of substances per country
        $substancesByCountry = DB::table('empodat_suspect_main as esm')
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
            ->orderBy('lc.name')
            ->get();

        $substancesByCountryData = [];
        foreach ($substancesByCountry as $stat) {
            $substancesByCountryData[$stat->country_name] = [
                'code' => $stat->country_code,
                'count' => $stat->substance_count,
            ];
        }

        Statistic::create([
            'database_entity_id' => $empodatSuspectEntity->id,
            'key' => 'empodat_suspect.substances_by_country',
            'meta_data' => [
                'data' => $substancesByCountryData,
                'generated_at' => now()->toISOString(),
                'total_countries' => count($substancesByCountryData),
            ]
        ]);

        // 5. Number of records per country
        $recordsByCountry = DB::table('empodat_suspect_main as esm')
            ->join('empodat_stations as es', 'esm.station_id', '=', 'es.id')
            ->join('list_countries as lc', 'es.country_id', '=', 'lc.id')
            ->select(
                'lc.name as country_name',
                'lc.code as country_code',
                DB::raw('COUNT(*) as record_count')
            )
            ->whereNotNull('es.country_id')
            ->groupBy('lc.name', 'lc.code')
            ->orderBy('lc.name')
            ->get();

        $recordsByCountryData = [];
        foreach ($recordsByCountry as $stat) {
            $recordsByCountryData[$stat->country_name] = [
                'code' => $stat->country_code,
                'count' => $stat->record_count,
            ];
        }

        Statistic::create([
            'database_entity_id' => $empodatSuspectEntity->id,
            'key' => 'empodat_suspect.records_by_country',
            'meta_data' => [
                'data' => $recordsByCountryData,
                'generated_at' => now()->toISOString(),
                'total_countries' => count($recordsByCountryData),
            ]
        ]);

        return back()->with('success', 'All statistics generated and stored successfully.');
    }

    /**
     * Display substances by sample code statistics
     */
    public function substancesBySampleCode()
    {
        $empodatSuspectEntity = DatabaseEntity::where('code', 'empodat_suspect')->first();

        if (!$empodatSuspectEntity) {
            return back()->with('error', 'Empodat Suspect database entity not found.');
        }

        $statisticsRecord = Statistic::where('database_entity_id', $empodatSuspectEntity->id)
            ->where('key', 'empodat_suspect.substances_by_sample_code')
            ->latest('created_at')
            ->first();

        if (!$statisticsRecord) {
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

        if (!$empodatSuspectEntity) {
            return back()->with('error', 'Empodat Suspect database entity not found.');
        }

        $statisticsRecord = Statistic::where('database_entity_id', $empodatSuspectEntity->id)
            ->where('key', 'empodat_suspect.records_by_sample_code')
            ->latest('created_at')
            ->first();

        if (!$statisticsRecord) {
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

        if (!$empodatSuspectEntity) {
            return back()->with('error', 'Empodat Suspect database entity not found.');
        }

        $statisticsRecord = Statistic::where('database_entity_id', $empodatSuspectEntity->id)
            ->where('key', 'empodat_suspect.substances_by_country')
            ->latest('created_at')
            ->first();

        if (!$statisticsRecord) {
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

        if (!$empodatSuspectEntity) {
            return back()->with('error', 'Empodat Suspect database entity not found.');
        }

        $statisticsRecord = Statistic::where('database_entity_id', $empodatSuspectEntity->id)
            ->where('key', 'empodat_suspect.records_by_country')
            ->latest('created_at')
            ->first();

        if (!$statisticsRecord) {
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
}
