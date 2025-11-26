<?php

declare(strict_types=1);

namespace App\Http\Controllers\Indoor;

use App\Http\Controllers\Controller;
use App\Models\DatabaseEntity;
use App\Models\Indoor\IndoorMain;
use App\Models\Statistic;
use Illuminate\Support\Facades\DB;

class IndoorStatisticsController extends Controller
{
    /**
     * Display statistics overview page
     */
    public function index()
    {
        $indoorEntity = DatabaseEntity::where('code', 'indoor')->first();
        $allStats = [];

        if ($indoorEntity) {
            $statisticKeys = Statistic::where('database_entity_id', $indoorEntity->id)
                ->distinct()
                ->pluck('key')
                ->toArray();

            foreach ($statisticKeys as $key) {
                $latestStat = Statistic::where('database_entity_id', $indoorEntity->id)
                    ->where('key', $key)
                    ->latest('created_at')
                    ->first();

                if ($latestStat) {
                    $allStats[$key] = $latestStat->meta_data;
                }
            }
        }

        $totalRecords = $indoorEntity->number_of_records ?? IndoorMain::count();

        return view('indoor.statistics.index', [
            'indoorEntity' => $indoorEntity,
            'allStats' => $allStats,
            'totalRecords' => $totalRecords,
        ]);
    }

    /**
     * Generate all statistics
     */
    public function generateAll()
    {
        $this->generateCountryStats();
        $this->generateMatrixStats();
        $this->generateEnvironmentTypeStats();
        $this->generateEnvironmentCategoryStats();
        $this->generateTotalsStats();

        session()->flash('success', 'All indoor statistics generated successfully.');

        return redirect()->back();
    }

    /**
     * Generate country statistics
     */
    public function generateCountryStats(): void
    {
        // Indoor uses indoor_data_country table with abbreviation field
        $statistics = DB::table('indoor_main as im')
            ->join('indoor_data_country as dc', 'im.country', '=', 'dc.abbreviation')
            ->select(
                'dc.name as country_name',
                'dc.abbreviation as country_code',
                DB::raw('COUNT(*) as record_count')
            )
            ->whereNotNull('im.country')
            ->where('im.country', '!=', '')
            ->groupBy('dc.name', 'dc.abbreviation')
            ->orderBy('record_count', 'desc')
            ->get();

        $countryStats = [];
        foreach ($statistics as $stat) {
            $countryStats[$stat->country_name] = [
                'code' => $stat->country_code,
                'count' => $stat->record_count,
            ];
        }

        $indoorEntity = DatabaseEntity::where('code', 'indoor')->first();

        if ($indoorEntity) {
            Statistic::create([
                'database_entity_id' => $indoorEntity->id,
                'key' => 'indoor.per_country',
                'meta_data' => [
                    'data' => $countryStats,
                    'generated_at' => now()->toISOString(),
                    'total_countries' => count($countryStats),
                ],
            ]);
        }
    }

    /**
     * Generate matrix statistics
     */
    public function generateMatrixStats(): void
    {
        $statistics = DB::table('indoor_main as im')
            ->join('indoor_data_matrix as dm', 'im.matrix_id', '=', 'dm.id')
            ->select(
                'dm.id as matrix_id',
                'dm.name as matrix_name',
                DB::raw('COUNT(*) as record_count')
            )
            ->whereNotNull('im.matrix_id')
            ->groupBy('dm.id', 'dm.name')
            ->orderBy('record_count', 'desc')
            ->get();

        $matrixStats = [];
        foreach ($statistics as $stat) {
            $matrixStats[$stat->matrix_name ?? 'Unknown'] = [
                'id' => $stat->matrix_id,
                'count' => $stat->record_count,
            ];
        }

        $indoorEntity = DatabaseEntity::where('code', 'indoor')->first();

        if ($indoorEntity) {
            Statistic::create([
                'database_entity_id' => $indoorEntity->id,
                'key' => 'indoor.per_matrix',
                'meta_data' => [
                    'data' => $matrixStats,
                    'generated_at' => now()->toISOString(),
                    'total_matrices' => count($matrixStats),
                ],
            ]);
        }
    }

    /**
     * Generate environment type statistics
     */
    public function generateEnvironmentTypeStats(): void
    {
        $statistics = DB::table('indoor_main as im')
            ->join('indoor_data_dtoe as dt', 'im.dtoe_id', '=', 'dt.id')
            ->select(
                'dt.id as type_id',
                'dt.name as type_name',
                DB::raw('COUNT(*) as record_count')
            )
            ->whereNotNull('im.dtoe_id')
            ->groupBy('dt.id', 'dt.name')
            ->orderBy('record_count', 'desc')
            ->get();

        $typeStats = [];
        foreach ($statistics as $stat) {
            $typeStats[$stat->type_name ?? 'Unknown'] = [
                'id' => $stat->type_id,
                'count' => $stat->record_count,
            ];
        }

        $indoorEntity = DatabaseEntity::where('code', 'indoor')->first();

        if ($indoorEntity) {
            Statistic::create([
                'database_entity_id' => $indoorEntity->id,
                'key' => 'indoor.per_environment_type',
                'meta_data' => [
                    'data' => $typeStats,
                    'generated_at' => now()->toISOString(),
                    'total_types' => count($typeStats),
                ],
            ]);
        }
    }

    /**
     * Generate environment category statistics
     */
    public function generateEnvironmentCategoryStats(): void
    {
        $statistics = DB::table('indoor_main as im')
            ->join('indoor_data_dcoe as dc', 'im.dcoe_id', '=', 'dc.id')
            ->select(
                'dc.id as category_id',
                'dc.name as category_name',
                DB::raw('COUNT(*) as record_count')
            )
            ->whereNotNull('im.dcoe_id')
            ->groupBy('dc.id', 'dc.name')
            ->orderBy('record_count', 'desc')
            ->get();

        $categoryStats = [];
        foreach ($statistics as $stat) {
            $categoryStats[$stat->category_name ?? 'Unknown'] = [
                'id' => $stat->category_id,
                'count' => $stat->record_count,
            ];
        }

        $indoorEntity = DatabaseEntity::where('code', 'indoor')->first();

        if ($indoorEntity) {
            Statistic::create([
                'database_entity_id' => $indoorEntity->id,
                'key' => 'indoor.per_environment_category',
                'meta_data' => [
                    'data' => $categoryStats,
                    'generated_at' => now()->toISOString(),
                    'total_categories' => count($categoryStats),
                ],
            ]);
        }
    }

    /**
     * Generate totals statistics
     */
    public function generateTotalsStats(): void
    {
        $totalRecords = IndoorMain::count();
        $totalCountries = DB::table('indoor_main')
            ->whereNotNull('country')
            ->where('country', '!=', '')
            ->distinct()
            ->count('country');
        $totalMatrices = DB::table('indoor_main')
            ->whereNotNull('matrix_id')
            ->distinct()
            ->count('matrix_id');
        $totalEnvironmentTypes = DB::table('indoor_main')
            ->whereNotNull('dtoe_id')
            ->distinct()
            ->count('dtoe_id');
        $totalEnvironmentCategories = DB::table('indoor_main')
            ->whereNotNull('dcoe_id')
            ->distinct()
            ->count('dcoe_id');

        $indoorEntity = DatabaseEntity::where('code', 'indoor')->first();

        if ($indoorEntity) {
            Statistic::create([
                'database_entity_id' => $indoorEntity->id,
                'key' => 'indoor.totals',
                'meta_data' => [
                    'total_records' => $totalRecords,
                    'total_countries' => $totalCountries,
                    'total_matrices' => $totalMatrices,
                    'total_environment_types' => $totalEnvironmentTypes,
                    'total_environment_categories' => $totalEnvironmentCategories,
                    'generated_at' => now()->toISOString(),
                ],
            ]);
        }
    }

    /**
     * View country statistics
     */
    public function perCountry()
    {
        $indoorEntity = DatabaseEntity::where('code', 'indoor')->first();

        if (! $indoorEntity) {
            return back()->with('error', 'Indoor database entity not found.');
        }

        $statisticsRecord = Statistic::where('database_entity_id', $indoorEntity->id)
            ->where('key', 'indoor.per_country')
            ->latest('created_at')
            ->first();

        if (! $statisticsRecord) {
            return view('indoor.statistics.per_country', [
                'data' => [],
                'totalCountries' => 0,
                'message' => 'No country statistics data available. Please generate the statistics first.',
            ]);
        }

        $data = $statisticsRecord->meta_data;

        return view('indoor.statistics.per_country', [
            'data' => $data['data'],
            'totalCountries' => $data['total_countries'],
            'generatedAt' => $data['generated_at'] ?? null,
        ]);
    }

    /**
     * View matrix statistics
     */
    public function perMatrix()
    {
        $indoorEntity = DatabaseEntity::where('code', 'indoor')->first();

        if (! $indoorEntity) {
            return back()->with('error', 'Indoor database entity not found.');
        }

        $statisticsRecord = Statistic::where('database_entity_id', $indoorEntity->id)
            ->where('key', 'indoor.per_matrix')
            ->latest('created_at')
            ->first();

        if (! $statisticsRecord) {
            return view('indoor.statistics.per_matrix', [
                'data' => [],
                'totalMatrices' => 0,
                'message' => 'No matrix statistics data available. Please generate the statistics first.',
            ]);
        }

        $data = $statisticsRecord->meta_data;

        return view('indoor.statistics.per_matrix', [
            'data' => $data['data'],
            'totalMatrices' => $data['total_matrices'],
            'generatedAt' => $data['generated_at'] ?? null,
        ]);
    }

    /**
     * View environment type statistics
     */
    public function perEnvironmentType()
    {
        $indoorEntity = DatabaseEntity::where('code', 'indoor')->first();

        if (! $indoorEntity) {
            return back()->with('error', 'Indoor database entity not found.');
        }

        $statisticsRecord = Statistic::where('database_entity_id', $indoorEntity->id)
            ->where('key', 'indoor.per_environment_type')
            ->latest('created_at')
            ->first();

        if (! $statisticsRecord) {
            return view('indoor.statistics.per_environment_type', [
                'data' => [],
                'totalTypes' => 0,
                'message' => 'No environment type statistics data available. Please generate the statistics first.',
            ]);
        }

        $data = $statisticsRecord->meta_data;

        return view('indoor.statistics.per_environment_type', [
            'data' => $data['data'],
            'totalTypes' => $data['total_types'],
            'generatedAt' => $data['generated_at'] ?? null,
        ]);
    }

    /**
     * View environment category statistics
     */
    public function perEnvironmentCategory()
    {
        $indoorEntity = DatabaseEntity::where('code', 'indoor')->first();

        if (! $indoorEntity) {
            return back()->with('error', 'Indoor database entity not found.');
        }

        $statisticsRecord = Statistic::where('database_entity_id', $indoorEntity->id)
            ->where('key', 'indoor.per_environment_category')
            ->latest('created_at')
            ->first();

        if (! $statisticsRecord) {
            return view('indoor.statistics.per_environment_category', [
                'data' => [],
                'totalCategories' => 0,
                'message' => 'No environment category statistics data available. Please generate the statistics first.',
            ]);
        }

        $data = $statisticsRecord->meta_data;

        return view('indoor.statistics.per_environment_category', [
            'data' => $data['data'],
            'totalCategories' => $data['total_categories'],
            'generatedAt' => $data['generated_at'] ?? null,
        ]);
    }
}
