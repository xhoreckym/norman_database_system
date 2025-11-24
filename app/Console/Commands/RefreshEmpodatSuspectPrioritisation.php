<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RefreshEmpodatSuspectPrioritisation extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'empodat-suspect:refresh-prioritisation
                            {--force : Force non-concurrent refresh (faster but blocks reads)}
                            {--create : Create the view if it doesn\'t exist}
                            {--stats : Show statistics after refresh}
                            {--limit= : Limit to first N records from empodat_suspect_main (default: 100000)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Refresh the Empodat Suspect prioritisation materialized view';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('╔══════════════════════════════════════════════════════════════════╗');
        $this->info('║  Empodat Suspect - Refresh Prioritisation Materialized View     ║');
        $this->info('╚══════════════════════════════════════════════════════════════════╝');
        $this->newLine();

        $limit = $this->option('limit') ?: 100000;
        $this->info("→ Processing limit: " . number_format($limit) . " records from empodat_suspect_main");
        $this->newLine();

        try {
            $startTime = microtime(true);

            // Check if the materialized view exists
            $viewExists = $this->checkViewExists();

            if (!$viewExists) {
                if ($this->option('create')) {
                    $this->warn('⚠ Materialized view does not exist. Creating it...');
                    $this->createView($limit);
                } else {
                    $this->error('✗ Materialized view does not exist!');
                    $this->info('Run with --create option to create it:');
                    $this->info('  php artisan empodat-suspect:refresh-prioritisation --create');
                    $this->newLine();
                    $this->info('Or run the migration:');
                    $this->info('  php artisan migrate');
                    return Command::FAILURE;
                }
            } else {
                $this->refreshView();
            }

            $duration = round(microtime(true) - $startTime, 2);

            // Show statistics if requested
            if ($this->option('stats')) {
                $this->newLine();
                $this->showStatistics();
            }

            $this->newLine();
            $this->info("✓ Materialized view is ready! (completed in {$duration}s)");

            Log::info('Empodat Suspect prioritisation refreshed successfully', [
                'duration' => $duration,
                'method' => $viewExists ? 'refresh' : 'create',
                'limit' => $limit
            ]);

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->newLine();
            $this->error('✗ Failed to refresh materialized view:');
            $this->error('  ' . $e->getMessage());

            if ($this->getOutput()->isVerbose()) {
                $this->newLine();
                $this->error($e->getTraceAsString());
            }

            Log::error('Empodat Suspect prioritisation refresh failed: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return Command::FAILURE;
        }
    }

    /**
     * Check if the materialized view exists
     */
    private function checkViewExists(): bool
    {
        $this->info('→ Checking if materialized view exists...');

        $result = DB::select("
            SELECT EXISTS (
                SELECT FROM pg_matviews
                WHERE schemaname = 'public'
                AND matviewname = 'empodat_suspect_prioritisation'
            ) as exists
        ");

        $exists = $result[0]->exists ?? false;

        if ($exists) {
            $this->info('  ✓ Materialized view exists');
        } else {
            $this->warn('  ✗ Materialized view does not exist');
        }

        return $exists;
    }

    /**
     * Refresh the existing materialized view
     */
    private function refreshView(): void
    {
        $concurrent = !$this->option('force');

        if ($concurrent) {
            $this->info('→ Refreshing materialized view (CONCURRENT mode - non-blocking)...');
            $this->line('  This allows reads during refresh but may take longer.');

            $refreshStart = microtime(true);
            DB::statement('REFRESH MATERIALIZED VIEW CONCURRENTLY empodat_suspect_prioritisation');
            $refreshDuration = round(microtime(true) - $refreshStart, 2);

            $this->info("  ✓ Concurrent refresh complete in {$refreshDuration}s");
        } else {
            $this->warn('→ Refreshing materialized view (FORCE mode - blocking)...');
            $this->line('  This will block all reads during refresh but is faster.');

            if (!$this->confirm('Continue with blocking refresh?', false)) {
                $this->info('  Cancelled. Use without --force for non-blocking refresh.');
                exit(Command::SUCCESS);
            }

            $refreshStart = microtime(true);
            DB::statement('REFRESH MATERIALIZED VIEW empodat_suspect_prioritisation');
            $refreshDuration = round(microtime(true) - $refreshStart, 2);

            $this->info("  ✓ Forced refresh complete in {$refreshDuration}s");
        }
    }

    /**
     * Create the materialized view from scratch
     */
    private function createView(int $limit): void
    {
        // Drop if exists
        $this->info('→ Dropping existing view (if any)...');
        DB::statement('DROP MATERIALIZED VIEW IF EXISTS empodat_suspect_prioritisation CASCADE');

        // Create the materialized view
        $this->info('→ Creating materialized view...');
        $this->info("  Using limit: " . number_format($limit) . " records");

        DB::statement("
            CREATE MATERIALIZED VIEW empodat_suspect_prioritisation AS
            WITH limited_suspect AS (
                -- Limit to first N records for testing
                SELECT * FROM empodat_suspect_main
                WHERE id <= {$limit}
            )
            SELECT
                -- Primary identifiers
                esm.id,
                em.id as empodat_main_id,

                -- Core fields from suspect data
                em.matrix_id,
                esm.concentration as concentration_value,
                esm.ip_max,

                -- Geographic and temporal information
                es.country_code as country,
                em.sampling_date_year as sampling_date_y,
                es.latitude as latitude_decimal,
                es.longitude as longitude_decimal,

                -- Substance information
                ss.code as sus_id,

                -- Matrix-specific fields using COALESCE
                -- basin_name (from biota or water_waste)
                COALESCE(
                    emb.basin_name,
                    emww.basin_name,
                    emws.basin_name,
                    emwg.basin_name
                ) as basin_name,

                -- df_id (from water_waste only)
                emww.df_id,

                -- dsa_id (from water_waste)
                emww.dsa_id,

                -- dsgr_id (from biota)
                emb.dsgr_id,

                -- dtiel_id (from biota)
                emb.dtiel_id,

                -- dmeas_id (from biota)
                emb.dmeas_id,

                -- Additional metadata
                em.method_id,
                em.station_id,
                esm.substance_id,
                em.dct_analysis_id,
                esm.based_on_hrms_library,
                esm.units,
                es.name as station_name,
                es.country_id,
                em.concentration_indicator_id,
                em.data_source_id,
                esm.created_at as suspect_created_at,
                em.created_at as empodat_created_at

            FROM limited_suspect esm

            -- Join to stations for geographic data
            INNER JOIN empodat_stations es
                ON esm.station_id = es.id

            -- Join to empodat_main for regular monitoring data
            INNER JOIN empodat_main em
                ON em.station_id = es.id

            -- Join to substances for substance code
            LEFT JOIN susdat_substances ss
                ON esm.substance_id = ss.id

            -- Matrix-specific LEFT JOINs based on matrix_id ranges

            -- Biota (matrix_id: 39-47)
            LEFT JOIN empodat_matrix_biota emb
                ON em.id = emb.id
                AND em.matrix_id BETWEEN 39 AND 47

            -- Water waste (matrix_id: 72-74)
            LEFT JOIN empodat_matrix_water_waste emww
                ON em.id = emww.id
                AND em.matrix_id BETWEEN 72 AND 74

            -- Water surface (matrix_id: specific IDs)
            LEFT JOIN empodat_matrix_water_surface emws
                ON em.id = emws.id
                AND em.matrix_id IN (2,3,4,5,6,7,8)

            -- Water ground (matrix_id: 1)
            LEFT JOIN empodat_matrix_water_ground emwg
                ON em.id = emwg.id
                AND em.matrix_id = 1

            WHERE esm.station_id IS NOT NULL
                AND es.id IS NOT NULL
                AND em.id IS NOT NULL
        ");

        $this->info('  ✓ Materialized view created');

        // Create indexes
        $this->info('→ Creating indexes...');
        $this->createIndexes();

        // Add comment
        DB::statement("
            COMMENT ON MATERIALIZED VIEW empodat_suspect_prioritisation IS
            'Comprehensive materialized view for Empodat Suspect prioritisation analysis.
            Combines suspect screening data with matrix-specific metadata.
            Limited to first {$limit} suspect records for testing.
            Refresh command: php artisan empodat-suspect:refresh-prioritisation'
        ");

        $this->info('  ✓ View setup complete');
    }

    /**
     * Create all required indexes on the materialized view
     */
    private function createIndexes(): void
    {
        $indexes = [
            'idx_esp_id' => 'id',
            'idx_esp_empodat_main_id' => 'empodat_main_id',
            'idx_esp_matrix_id' => 'matrix_id',
            'idx_esp_station_id' => 'station_id',
            'idx_esp_substance_id' => 'substance_id',
            'idx_esp_country' => 'country',
            'idx_esp_country_id' => 'country_id',
            'idx_esp_year' => 'sampling_date_y',
            'idx_esp_sus_id' => 'sus_id',
            'idx_esp_ip_max' => 'ip_max',
        ];

        $indexCount = 0;
        foreach ($indexes as $indexName => $column) {
            DB::statement("CREATE INDEX IF NOT EXISTS {$indexName} ON empodat_suspect_prioritisation({$column})");
            $indexCount++;
        }

        // Partial indexes for matrix-specific fields
        $partialIndexes = [
            'idx_esp_basin_name' => 'basin_name',
            'idx_esp_df_id' => 'df_id',
            'idx_esp_dsa_id' => 'dsa_id',
            'idx_esp_dsgr_id' => 'dsgr_id',
            'idx_esp_dtiel_id' => 'dtiel_id',
            'idx_esp_dmeas_id' => 'dmeas_id',
        ];

        foreach ($partialIndexes as $indexName => $column) {
            DB::statement("CREATE INDEX IF NOT EXISTS {$indexName} ON empodat_suspect_prioritisation({$column}) WHERE {$column} IS NOT NULL");
            $indexCount++;
        }

        // Compound indexes
        DB::statement('CREATE INDEX IF NOT EXISTS idx_esp_station_substance ON empodat_suspect_prioritisation(station_id, substance_id)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_esp_matrix_year ON empodat_suspect_prioritisation(matrix_id, sampling_date_y)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_esp_country_matrix ON empodat_suspect_prioritisation(country, matrix_id)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_esp_lat_lon ON empodat_suspect_prioritisation(latitude_decimal, longitude_decimal)');
        $indexCount += 4;

        // UNIQUE index for CONCURRENT refresh support
        DB::statement('
            CREATE UNIQUE INDEX IF NOT EXISTS idx_esp_unique_combo
            ON empodat_suspect_prioritisation(
                id,
                empodat_main_id
            )
        ');
        $indexCount++;

        $this->info("  ✓ {$indexCount} indexes created");
    }

    /**
     * Show statistics about the materialized view
     */
    private function showStatistics(): void
    {
        $this->info('╔════════════════════════════════════════╗');
        $this->info('║         View Statistics                ║');
        $this->info('╚════════════════════════════════════════╝');

        try {
            // Get row count
            $rowCount = DB::table('empodat_suspect_prioritisation')->count();
            $this->line("  Total records:            " . number_format($rowCount));

            // Get unique stations
            $stationCount = DB::table('empodat_suspect_prioritisation')
                ->distinct('station_id')
                ->count('station_id');
            $this->line("  Unique stations:          " . number_format($stationCount));

            // Get unique countries
            $countryCount = DB::table('empodat_suspect_prioritisation')
                ->distinct('country')
                ->count('country');
            $this->line("  Unique countries:         " . number_format($countryCount));

            // Get unique substances
            $substanceCount = DB::table('empodat_suspect_prioritisation')
                ->distinct('substance_id')
                ->count('substance_id');
            $this->line("  Unique substances:        " . number_format($substanceCount));

            // Get matrix distribution
            $matrixDistribution = DB::table('empodat_suspect_prioritisation')
                ->select('matrix_id', DB::raw('count(*) as count'))
                ->groupBy('matrix_id')
                ->orderBy('count', 'desc')
                ->limit(5)
                ->get();

            $this->line("\n  Top 5 matrices by record count:");
            foreach ($matrixDistribution as $matrix) {
                $this->line("    Matrix {$matrix->matrix_id}: " . number_format($matrix->count));
            }

            // Get records with matrix-specific data
            $biotaCount = DB::table('empodat_suspect_prioritisation')
                ->whereNotNull('dsgr_id')
                ->count();
            $this->line("\n  Records with biota data:  " . number_format($biotaCount));

            $waterWasteCount = DB::table('empodat_suspect_prioritisation')
                ->whereNotNull('df_id')
                ->count();
            $this->line("  Records with water waste data: " . number_format($waterWasteCount));

            // Get view size
            $sizeResult = DB::select("
                SELECT pg_size_pretty(pg_total_relation_size('empodat_suspect_prioritisation')) as size
            ");
            $this->line("\n  Total size (with indexes): " . ($sizeResult[0]->size ?? 'N/A'));

            // Get data-only size
            $mvInfo = DB::select("
                SELECT pg_size_pretty(pg_relation_size('empodat_suspect_prioritisation')) as size
            ");
            $this->line("  View size (data only):     " . ($mvInfo[0]->size ?? 'N/A'));

            // Count non-null values for sparse columns
            $this->line("\n  Non-null values in matrix-specific columns:");
            $sparseColumns = ['basin_name', 'df_id', 'dsa_id', 'dsgr_id', 'dtiel_id', 'dmeas_id'];
            foreach ($sparseColumns as $column) {
                $nonNullCount = DB::table('empodat_suspect_prioritisation')
                    ->whereNotNull($column)
                    ->count();
                $percentage = $rowCount > 0 ? round(($nonNullCount / $rowCount) * 100, 2) : 0;
                $this->line("    {$column}: " . number_format($nonNullCount) . " ({$percentage}%)");
            }

        } catch (\Exception $e) {
            $this->warn('  Could not retrieve all statistics: ' . $e->getMessage());
        }
    }
}