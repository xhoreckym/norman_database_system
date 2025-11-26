<?php

declare(strict_types=1);

namespace App\Http\Controllers\Sars;

use App\Http\Controllers\Controller;
use App\Models\DatabaseEntity;
use App\Models\Sars\SarsMain;
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
     * Check if user has access to the SARS module
     */
    private function checkModuleAccess(): void
    {
        $databaseEntity = DatabaseEntity::where('code', 'sars')->first();

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
        if ($user->hasRole('sars')) {
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
        $sarsEntity = DatabaseEntity::where('code', 'sars')->first();
        $allStats = [];

        if ($sarsEntity) {
            $statisticKeys = Statistic::where('database_entity_id', $sarsEntity->id)
                ->distinct()
                ->pluck('key')
                ->toArray();

            foreach ($statisticKeys as $key) {
                $latestStat = Statistic::where('database_entity_id', $sarsEntity->id)
                    ->where('key', $key)
                    ->latest('created_at')
                    ->first();

                if ($latestStat) {
                    $allStats[$key] = $latestStat->meta_data;
                }
            }
        }

        $totalRecords = $sarsEntity->number_of_records ?? SarsMain::count();

        return view('sars.statistics.index', [
            'sarsEntity' => $sarsEntity,
            'allStats' => $allStats,
            'totalRecords' => $totalRecords,
        ]);
    }

    /**
     * Generate and store all statistics data
     */
    public function generateStatistics()
    {
        $sarsEntity = DatabaseEntity::where('code', 'sars')->first();

        if (! $sarsEntity) {
            return back()->with('error', 'SARS database entity not found.');
        }

        $this->generateCountryStats($sarsEntity);
        $this->generateMatrixStats($sarsEntity);
        $this->generateYearStats($sarsEntity);
        $this->generateTotalsStats($sarsEntity);

        return back()->with('success', 'All statistics generated and stored successfully.');
    }

    /**
     * Generate country statistics
     */
    private function generateCountryStats(DatabaseEntity $entity): void
    {
        // Records per country (direct column, no join needed)
        $recordsByCountry = DB::table('sars_cov_main')
            ->select(
                'name_of_country as country_name',
                DB::raw('COUNT(*) as record_count')
            )
            ->whereNotNull('name_of_country')
            ->where('name_of_country', '!=', '')
            ->groupBy('name_of_country')
            ->orderBy('record_count', 'desc')
            ->get();

        $recordsByCountryData = [];
        foreach ($recordsByCountry as $stat) {
            $recordsByCountryData[$stat->country_name] = [
                'count' => $stat->record_count,
            ];
        }

        Statistic::create([
            'database_entity_id' => $entity->id,
            'key' => 'sars.per_country',
            'meta_data' => [
                'data' => $recordsByCountryData,
                'generated_at' => now()->toISOString(),
                'total_countries' => count($recordsByCountryData),
            ],
        ]);
    }

    /**
     * Generate matrix/ecosystem statistics
     */
    private function generateMatrixStats(DatabaseEntity $entity): void
    {
        // Records per sample matrix (ecosystem)
        $recordsByMatrix = DB::table('sars_cov_main')
            ->select(
                'sample_matrix as matrix_name',
                DB::raw('COUNT(*) as record_count')
            )
            ->whereNotNull('sample_matrix')
            ->where('sample_matrix', '!=', '')
            ->groupBy('sample_matrix')
            ->orderBy('record_count', 'desc')
            ->get();

        $recordsByMatrixData = [];
        foreach ($recordsByMatrix as $stat) {
            $recordsByMatrixData[$stat->matrix_name] = [
                'count' => $stat->record_count,
            ];
        }

        Statistic::create([
            'database_entity_id' => $entity->id,
            'key' => 'sars.per_matrix',
            'meta_data' => [
                'data' => $recordsByMatrixData,
                'generated_at' => now()->toISOString(),
                'total_matrices' => count($recordsByMatrixData),
            ],
        ]);
    }

    /**
     * Generate year statistics
     */
    private function generateYearStats(DatabaseEntity $entity): void
    {
        $recordsByYear = DB::table('sars_cov_main')
            ->select(
                'sample_from_year as year',
                DB::raw('COUNT(*) as record_count')
            )
            ->whereNotNull('sample_from_year')
            ->where('sample_from_year', '>', 0)
            ->groupBy('sample_from_year')
            ->orderBy('sample_from_year', 'desc')
            ->get();

        $recordsByYearData = [];
        foreach ($recordsByYear as $stat) {
            $recordsByYearData[(string) $stat->year] = $stat->record_count;
        }

        Statistic::create([
            'database_entity_id' => $entity->id,
            'key' => 'sars.per_year',
            'meta_data' => [
                'data' => $recordsByYearData,
                'generated_at' => now()->toISOString(),
                'total_years' => count($recordsByYearData),
            ],
        ]);
    }

    /**
     * Generate totals statistics
     */
    private function generateTotalsStats(DatabaseEntity $entity): void
    {
        $totalRecords = SarsMain::count();

        $totalCountries = SarsMain::distinct('name_of_country')
            ->whereNotNull('name_of_country')
            ->where('name_of_country', '!=', '')
            ->count('name_of_country');

        $totalMatrices = SarsMain::distinct('sample_matrix')
            ->whereNotNull('sample_matrix')
            ->where('sample_matrix', '!=', '')
            ->count('sample_matrix');

        $totalStations = SarsMain::distinct('station_name')
            ->whereNotNull('station_name')
            ->where('station_name', '!=', '')
            ->count('station_name');

        Statistic::create([
            'database_entity_id' => $entity->id,
            'key' => 'sars.totals',
            'meta_data' => [
                'total_records' => $totalRecords,
                'total_countries' => $totalCountries,
                'total_matrices' => $totalMatrices,
                'total_stations' => $totalStations,
                'generated_at' => now()->toISOString(),
            ],
        ]);
    }

    /**
     * Display records by country statistics
     */
    public function perCountry()
    {
        $sarsEntity = DatabaseEntity::where('code', 'sars')->first();

        if (! $sarsEntity) {
            return back()->with('error', 'SARS database entity not found.');
        }

        $statisticsRecord = Statistic::where('database_entity_id', $sarsEntity->id)
            ->where('key', 'sars.per_country')
            ->latest('created_at')
            ->first();

        if (! $statisticsRecord) {
            return view('sars.statistics.per_country', [
                'data' => [],
                'message' => 'No statistics available. Please generate statistics first.',
                'generatedAt' => null,
            ]);
        }

        return view('sars.statistics.per_country', [
            'data' => $statisticsRecord->meta_data['data'] ?? [],
            'totalCountries' => $statisticsRecord->meta_data['total_countries'] ?? 0,
            'generatedAt' => $statisticsRecord->meta_data['generated_at'] ?? null,
            'message' => null,
        ]);
    }

    /**
     * Display records by matrix/ecosystem statistics
     */
    public function perMatrix()
    {
        $sarsEntity = DatabaseEntity::where('code', 'sars')->first();

        if (! $sarsEntity) {
            return back()->with('error', 'SARS database entity not found.');
        }

        $statisticsRecord = Statistic::where('database_entity_id', $sarsEntity->id)
            ->where('key', 'sars.per_matrix')
            ->latest('created_at')
            ->first();

        if (! $statisticsRecord) {
            return view('sars.statistics.per_matrix', [
                'data' => [],
                'message' => 'No statistics available. Please generate statistics first.',
                'generatedAt' => null,
            ]);
        }

        return view('sars.statistics.per_matrix', [
            'data' => $statisticsRecord->meta_data['data'] ?? [],
            'totalMatrices' => $statisticsRecord->meta_data['total_matrices'] ?? 0,
            'generatedAt' => $statisticsRecord->meta_data['generated_at'] ?? null,
            'message' => null,
        ]);
    }

    /**
     * Display records by year statistics
     */
    public function perYear()
    {
        $sarsEntity = DatabaseEntity::where('code', 'sars')->first();

        if (! $sarsEntity) {
            return back()->with('error', 'SARS database entity not found.');
        }

        $statisticsRecord = Statistic::where('database_entity_id', $sarsEntity->id)
            ->where('key', 'sars.per_year')
            ->latest('created_at')
            ->first();

        if (! $statisticsRecord) {
            return view('sars.statistics.per_year', [
                'data' => [],
                'message' => 'No statistics available. Please generate statistics first.',
                'generatedAt' => null,
            ]);
        }

        return view('sars.statistics.per_year', [
            'data' => $statisticsRecord->meta_data['data'] ?? [],
            'totalYears' => $statisticsRecord->meta_data['total_years'] ?? 0,
            'generatedAt' => $statisticsRecord->meta_data['generated_at'] ?? null,
            'message' => null,
        ]);
    }
}
