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
        $this->info('→ Processing limit: '.number_format($limit).' records from empodat_suspect_main');
        $this->newLine();

        try {
            $startTime = microtime(true);

            // Check if the materialized view exists
            $viewExists = $this->checkViewExists();

            if (! $viewExists) {
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
                'limit' => $limit,
            ]);

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->newLine();
            $this->error('✗ Failed to refresh materialized view:');
            $this->error('  '.$e->getMessage());

            if ($this->getOutput()->isVerbose()) {
                $this->newLine();
                $this->error($e->getTraceAsString());
            }

            Log::error('Empodat Suspect prioritisation refresh failed: '.$e->getMessage(), [
                'trace' => $e->getTraceAsString(),
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
        $concurrent = ! $this->option('force');

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

            if (! $this->confirm('Continue with blocking refresh?', false)) {
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
        $this->info('  Using limit: '.number_format($limit).' records');

        DB::statement("
            CREATE MATERIALIZED VIEW empodat_suspect_prioritisation AS
            WITH limited_suspect AS (
                -- Limit to first N records
                SELECT * FROM empodat_suspect_main
                ORDER BY id
                LIMIT {$limit}
            ),
            most_recent_main AS (
                -- Get most recent empodat_main record per station
                -- This prevents Cartesian product and massive memory usage
                SELECT DISTINCT ON (station_id)
                    id,
                    station_id,
                    matrix_id,
                    sampling_date_year
                FROM empodat_main
                WHERE station_id IN (SELECT DISTINCT station_id FROM limited_suspect)
                ORDER BY station_id, sampling_date_year DESC NULLS LAST
            ),
            -- Calculate am_loq: minimum concentration divided by 3 for each matrix + substance combination
            min_concentration_by_matrix_substance AS (
                SELECT
                    em.matrix_id,
                    esm.substance_id,
                    MIN(esm.concentration) / 3.0 AS am_loq
                FROM limited_suspect esm
                INNER JOIN most_recent_main em ON em.station_id = esm.station_id
                WHERE esm.concentration IS NOT NULL
                    AND esm.concentration > 0
                GROUP BY em.matrix_id, esm.substance_id
            ),
            -- Calculate max_ip_max: maximum ip_max for each matrix + substance combination
            max_ip_by_matrix_substance AS (
                SELECT
                    em.matrix_id,
                    esm.substance_id,
                    MAX(esm.ip_max) AS max_ip_max
                FROM limited_suspect esm
                INNER JOIN most_recent_main em ON em.station_id = esm.station_id
                WHERE esm.ip_max IS NOT NULL
                GROUP BY em.matrix_id, esm.substance_id
            )
            SELECT
                -- Primary identifiers
                esm.id,

                -- Core fields from suspect data
                em.matrix_id AS matrix,
                esm.concentration AS concentration_value,
                mcms.am_loq,
                esm.ip_max,
                mims.max_ip_max,

                -- Geographic and temporal information
                es.country AS country,
                esm.station_id AS station_name,
                em.sampling_date_year AS sampling_date_y,
                es.latitude AS latitude_decimal,
                es.longitude AS longitude_decimal,

                -- Substance information
                ss.code AS sus_id,

                -- Matrix-specific fields using COALESCE
                -- basin_name (from biota or water_waste)
                COALESCE(
                    emb.basin_name,
                    emww.basin_name,
                    emws.basin_name,
                    emwg.basin_name
                ) AS basin_name,

                -- df_id (from water_waste only)
                emww.df_id,

                -- dsa_id (from water_waste)
                emww.dsa_id,

                -- dsgr_id (from biota)
                emb.dsgr_id,

                -- dtiel_id (from biota)
                emb.dtiel_id,

                -- dmeas_id (from biota)
                emb.dmeas_id

            FROM limited_suspect esm

            -- Join to stations for geographic data
            INNER JOIN empodat_stations es
                ON esm.station_id = es.id

            -- Join to most recent empodat_main record per station
            LEFT JOIN most_recent_main em
                ON em.station_id = esm.station_id

            -- Join to substances for substance code
            LEFT JOIN susdat_substances ss
                ON esm.substance_id = ss.id

            -- Join to pre-calculated am_loq
            LEFT JOIN min_concentration_by_matrix_substance mcms
                ON em.matrix_id = mcms.matrix_id
                AND esm.substance_id = mcms.substance_id

            -- Join to pre-calculated max_ip_max
            LEFT JOIN max_ip_by_matrix_substance mims
                ON em.matrix_id = mims.matrix_id
                AND esm.substance_id = mims.substance_id

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
            Includes am_loq (min concentration / 3 per matrix+substance) and max_ip_max.
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
            'idx_esp_matrix' => 'matrix',
            'idx_esp_country' => 'country',
            'idx_esp_year' => 'sampling_date_y',
            'idx_esp_sus_id' => 'sus_id',
            'idx_esp_ip_max' => 'ip_max',
            'idx_esp_station_name' => 'station_name',
            'idx_esp_max_ip_max' => 'max_ip_max',
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
            'idx_esp_am_loq' => 'am_loq',
        ];

        foreach ($partialIndexes as $indexName => $column) {
            DB::statement("CREATE INDEX IF NOT EXISTS {$indexName} ON empodat_suspect_prioritisation({$column}) WHERE {$column} IS NOT NULL");
            $indexCount++;
        }

        // Compound indexes
        DB::statement('CREATE INDEX IF NOT EXISTS idx_esp_matrix_year ON empodat_suspect_prioritisation(matrix, sampling_date_y)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_esp_country_matrix ON empodat_suspect_prioritisation(country, matrix)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_esp_lat_lon ON empodat_suspect_prioritisation(latitude_decimal, longitude_decimal)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_esp_matrix_substance ON empodat_suspect_prioritisation(matrix, sus_id)');
        $indexCount += 4;

        // UNIQUE index for CONCURRENT refresh support
        DB::statement('
            CREATE UNIQUE INDEX IF NOT EXISTS idx_esp_unique_id
            ON empodat_suspect_prioritisation(id)
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
            $this->line('  Total records:            '.number_format($rowCount));

            // Get unique stations
            $stationCount = DB::table('empodat_suspect_prioritisation')
                ->distinct('station_name')
                ->count('station_name');
            $this->line('  Unique stations:          '.number_format($stationCount));

            // Get unique countries
            $countryCount = DB::table('empodat_suspect_prioritisation')
                ->distinct('country')
                ->count('country');
            $this->line('  Unique countries:         '.number_format($countryCount));

            // Get unique substances
            $substanceCount = DB::table('empodat_suspect_prioritisation')
                ->distinct('sus_id')
                ->count('sus_id');
            $this->line('  Unique substances:        '.number_format($substanceCount));

            // Get matrix distribution
            $matrixDistribution = DB::table('empodat_suspect_prioritisation')
                ->select('matrix', DB::raw('count(*) as count'))
                ->groupBy('matrix')
                ->orderBy('count', 'desc')
                ->limit(5)
                ->get();

            $this->line("\n  Top 5 matrices by record count:");
            foreach ($matrixDistribution as $matrix) {
                $this->line("    Matrix {$matrix->matrix}: ".number_format($matrix->count));
            }

            // Get records with matrix-specific data
            $biotaCount = DB::table('empodat_suspect_prioritisation')
                ->whereNotNull('dsgr_id')
                ->count();
            $this->line("\n  Records with biota data:  ".number_format($biotaCount));

            $waterWasteCount = DB::table('empodat_suspect_prioritisation')
                ->whereNotNull('df_id')
                ->count();
            $this->line('  Records with water waste data: '.number_format($waterWasteCount));

            // Get records with am_loq
            $amLoqCount = DB::table('empodat_suspect_prioritisation')
                ->whereNotNull('am_loq')
                ->count();
            $this->line('  Records with am_loq:      '.number_format($amLoqCount));

            // Get records with max_ip_max
            $maxIpMaxCount = DB::table('empodat_suspect_prioritisation')
                ->whereNotNull('max_ip_max')
                ->count();
            $this->line('  Records with max_ip_max:  '.number_format($maxIpMaxCount));

            // Get view size
            $sizeResult = DB::select("
                SELECT pg_size_pretty(pg_total_relation_size('empodat_suspect_prioritisation')) as size
            ");
            $this->line("\n  Total size (with indexes): ".($sizeResult[0]->size ?? 'N/A'));

            // Get data-only size
            $mvInfo = DB::select("
                SELECT pg_size_pretty(pg_relation_size('empodat_suspect_prioritisation')) as size
            ");
            $this->line('  View size (data only):     '.($mvInfo[0]->size ?? 'N/A'));

            // Count non-null values for sparse columns
            $this->line("\n  Non-null values in matrix-specific columns:");
            $sparseColumns = ['basin_name', 'df_id', 'dsa_id', 'dsgr_id', 'dtiel_id', 'dmeas_id', 'am_loq', 'max_ip_max'];
            foreach ($sparseColumns as $column) {
                $nonNullCount = DB::table('empodat_suspect_prioritisation')
                    ->whereNotNull($column)
                    ->count();
                $percentage = $rowCount > 0 ? round(($nonNullCount / $rowCount) * 100, 2) : 0;
                $this->line("    {$column}: ".number_format($nonNullCount)." ({$percentage}%)");
            }

        } catch (\Exception $e) {
            $this->warn('  Could not retrieve all statistics: '.$e->getMessage());
        }
    }
}
