<?php

declare(strict_types=1);

namespace App\Http\Controllers\Passive;

use App\Http\Controllers\Controller;
use App\Models\DatabaseEntity;
use App\Models\Passive\PassiveSamplingMain;
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
     * Check if user has access to the Passive module
     */
    private function checkModuleAccess(): void
    {
        $databaseEntity = DatabaseEntity::where('code', 'passive')->first();

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
        if ($user->hasRole('passive')) {
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
        $passiveEntity = DatabaseEntity::where('code', 'passive')->first();
        $allStats = [];

        if ($passiveEntity) {
            $statisticKeys = Statistic::where('database_entity_id', $passiveEntity->id)
                ->distinct()
                ->pluck('key')
                ->toArray();

            foreach ($statisticKeys as $key) {
                $latestStat = Statistic::where('database_entity_id', $passiveEntity->id)
                    ->where('key', $key)
                    ->latest('created_at')
                    ->first();

                if ($latestStat) {
                    $allStats[$key] = $latestStat->meta_data;
                }
            }
        }

        $totalRecords = $passiveEntity->number_of_records ?? PassiveSamplingMain::count();

        return view('passive.statistics.index', [
            'passiveEntity' => $passiveEntity,
            'allStats' => $allStats,
            'totalRecords' => $totalRecords,
        ]);
    }

    /**
     * Generate and store all statistics data
     */
    public function generateStatistics()
    {
        $passiveEntity = DatabaseEntity::where('code', 'passive')->first();

        if (! $passiveEntity) {
            return back()->with('error', 'Passive database entity not found.');
        }

        $this->generateCountryStats($passiveEntity);
        $this->generateMatrixStats($passiveEntity);
        $this->generateSubstanceStats($passiveEntity);
        $this->generateTotalsStats($passiveEntity);

        return back()->with('success', 'All statistics generated and stored successfully.');
    }

    /**
     * Generate country statistics
     */
    private function generateCountryStats(DatabaseEntity $entity): void
    {
        // Records per country
        $recordsByCountry = DB::table('passive_sampling_main as psm')
            ->join('passive_data_country as pdc', 'psm.country_id', '=', 'pdc.id')
            ->select(
                'pdc.name as country_name',
                'pdc.abbreviation as country_code',
                DB::raw('COUNT(*) as record_count')
            )
            ->whereNotNull('psm.country_id')
            ->groupBy('pdc.name', 'pdc.abbreviation')
            ->orderBy('record_count', 'desc')
            ->get();

        $recordsByCountryData = [];
        foreach ($recordsByCountry as $stat) {
            $recordsByCountryData[$stat->country_name] = [
                'code' => $stat->country_code,
                'count' => $stat->record_count,
            ];
        }

        Statistic::create([
            'database_entity_id' => $entity->id,
            'key' => 'passive.per_country',
            'meta_data' => [
                'data' => $recordsByCountryData,
                'generated_at' => now()->toISOString(),
                'total_countries' => count($recordsByCountryData),
            ],
        ]);
    }

    /**
     * Generate matrix statistics
     */
    private function generateMatrixStats(DatabaseEntity $entity): void
    {
        $recordsByMatrix = DB::table('passive_sampling_main as psm')
            ->join('passive_data_matrix as pdm', 'psm.matrix_id', '=', 'pdm.id')
            ->select(
                'pdm.name as matrix_name',
                'pdm.id as matrix_id',
                DB::raw('COUNT(*) as record_count')
            )
            ->whereNotNull('psm.matrix_id')
            ->groupBy('pdm.name', 'pdm.id')
            ->orderBy('record_count', 'desc')
            ->get();

        $recordsByMatrixData = [];
        foreach ($recordsByMatrix as $stat) {
            $recordsByMatrixData[$stat->matrix_name] = [
                'id' => $stat->matrix_id,
                'count' => $stat->record_count,
            ];
        }

        Statistic::create([
            'database_entity_id' => $entity->id,
            'key' => 'passive.per_matrix',
            'meta_data' => [
                'data' => $recordsByMatrixData,
                'generated_at' => now()->toISOString(),
                'total_matrices' => count($recordsByMatrixData),
            ],
        ]);
    }

    /**
     * Generate substance statistics
     */
    private function generateSubstanceStats(DatabaseEntity $entity): void
    {
        // Total unique substances
        $totalSubstances = PassiveSamplingMain::distinct('sus_id')
            ->whereNotNull('sus_id')
            ->count('sus_id');

        // Records per substance (top substances)
        $recordsBySubstance = DB::table('passive_sampling_main as psm')
            ->join('susdat_substances as ss', 'psm.sus_id', '=', 'ss.id')
            ->select(
                'ss.name as substance_name',
                'ss.id as substance_id',
                'ss.code as substance_code',
                DB::raw('COUNT(*) as record_count')
            )
            ->whereNotNull('psm.sus_id')
            ->groupBy('ss.name', 'ss.id', 'ss.code')
            ->orderBy('record_count', 'desc')
            ->get();

        $recordsBySubstanceData = [];
        foreach ($recordsBySubstance as $stat) {
            $recordsBySubstanceData[$stat->substance_name] = [
                'id' => $stat->substance_id,
                'code' => $stat->substance_code,
                'count' => $stat->record_count,
            ];
        }

        Statistic::create([
            'database_entity_id' => $entity->id,
            'key' => 'passive.per_substance',
            'meta_data' => [
                'data' => $recordsBySubstanceData,
                'generated_at' => now()->toISOString(),
                'total_substances' => $totalSubstances,
            ],
        ]);
    }

    /**
     * Generate totals statistics
     */
    private function generateTotalsStats(DatabaseEntity $entity): void
    {
        $totalRecords = PassiveSamplingMain::count();
        $totalSubstances = PassiveSamplingMain::distinct('sus_id')
            ->whereNotNull('sus_id')
            ->count('sus_id');
        $totalCountries = PassiveSamplingMain::distinct('country_id')
            ->whereNotNull('country_id')
            ->count('country_id');
        $totalMatrices = PassiveSamplingMain::distinct('matrix_id')
            ->whereNotNull('matrix_id')
            ->count('matrix_id');

        Statistic::create([
            'database_entity_id' => $entity->id,
            'key' => 'passive.totals',
            'meta_data' => [
                'total_records' => $totalRecords,
                'total_substances' => $totalSubstances,
                'total_countries' => $totalCountries,
                'total_matrices' => $totalMatrices,
                'generated_at' => now()->toISOString(),
            ],
        ]);
    }

    /**
     * Display records by country statistics
     */
    public function perCountry()
    {
        $passiveEntity = DatabaseEntity::where('code', 'passive')->first();

        if (! $passiveEntity) {
            return back()->with('error', 'Passive database entity not found.');
        }

        $statisticsRecord = Statistic::where('database_entity_id', $passiveEntity->id)
            ->where('key', 'passive.per_country')
            ->latest('created_at')
            ->first();

        if (! $statisticsRecord) {
            return view('passive.statistics.per_country', [
                'data' => [],
                'message' => 'No statistics available. Please generate statistics first.',
                'generatedAt' => null,
            ]);
        }

        return view('passive.statistics.per_country', [
            'data' => $statisticsRecord->meta_data['data'] ?? [],
            'totalCountries' => $statisticsRecord->meta_data['total_countries'] ?? 0,
            'generatedAt' => $statisticsRecord->meta_data['generated_at'] ?? null,
            'message' => null,
        ]);
    }

    /**
     * Display records by matrix statistics
     */
    public function perMatrix()
    {
        $passiveEntity = DatabaseEntity::where('code', 'passive')->first();

        if (! $passiveEntity) {
            return back()->with('error', 'Passive database entity not found.');
        }

        $statisticsRecord = Statistic::where('database_entity_id', $passiveEntity->id)
            ->where('key', 'passive.per_matrix')
            ->latest('created_at')
            ->first();

        if (! $statisticsRecord) {
            return view('passive.statistics.per_matrix', [
                'data' => [],
                'message' => 'No statistics available. Please generate statistics first.',
                'generatedAt' => null,
            ]);
        }

        return view('passive.statistics.per_matrix', [
            'data' => $statisticsRecord->meta_data['data'] ?? [],
            'totalMatrices' => $statisticsRecord->meta_data['total_matrices'] ?? 0,
            'generatedAt' => $statisticsRecord->meta_data['generated_at'] ?? null,
            'message' => null,
        ]);
    }

    /**
     * Display records by substance statistics
     */
    public function perSubstance()
    {
        $passiveEntity = DatabaseEntity::where('code', 'passive')->first();

        if (! $passiveEntity) {
            return back()->with('error', 'Passive database entity not found.');
        }

        $statisticsRecord = Statistic::where('database_entity_id', $passiveEntity->id)
            ->where('key', 'passive.per_substance')
            ->latest('created_at')
            ->first();

        if (! $statisticsRecord) {
            return view('passive.statistics.per_substance', [
                'data' => [],
                'message' => 'No statistics available. Please generate statistics first.',
                'generatedAt' => null,
            ]);
        }

        return view('passive.statistics.per_substance', [
            'data' => $statisticsRecord->meta_data['data'] ?? [],
            'totalSubstances' => $statisticsRecord->meta_data['total_substances'] ?? 0,
            'generatedAt' => $statisticsRecord->meta_data['generated_at'] ?? null,
            'message' => null,
        ]);
    }
}
