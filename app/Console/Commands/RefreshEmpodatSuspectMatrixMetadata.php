<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RefreshEmpodatSuspectMatrixMetadata extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'empodat-suspect:refresh-matrix-metadata
                            {--force : Force non-concurrent refresh (faster but blocks reads)}
                            {--create : Create the views if they don\'t exist}
                            {--stats : Show statistics after refresh}
                            {--only= : Only refresh specific matrix type (biota, sediments, water_surface, water_ground, water_waste, suspended_matter, soil, air, sewage_sludge)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Refresh the Empodat Suspect matrix metadata materialized views (used for CSV exports)';

    /**
     * Matrix types and their MV names
     */
    protected array $matrixTypes = [
        'biota' => 'empodat_suspect_matrix_biota',
        'sediments' => 'empodat_suspect_matrix_sediments',
        'water_surface' => 'empodat_suspect_matrix_water_surface',
        'water_ground' => 'empodat_suspect_matrix_water_ground',
        'water_waste' => 'empodat_suspect_matrix_water_waste',
        'suspended_matter' => 'empodat_suspect_matrix_suspended_matter',
        'soil' => 'empodat_suspect_matrix_soil',
        'air' => 'empodat_suspect_matrix_air',
        'sewage_sludge' => 'empodat_suspect_matrix_sewage_sludge',
    ];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('╔══════════════════════════════════════════════════════════════════╗');
        $this->info('║  Empodat Suspect - Refresh Matrix Metadata Materialized Views   ║');
        $this->info('╚══════════════════════════════════════════════════════════════════╝');
        $this->newLine();

        try {
            $startTime = microtime(true);

            // Check prerequisite: helper table must exist
            if (! $this->checkHelperTableExists()) {
                $this->error('✗ Prerequisite not met: empodat_suspect_stations_helper table does not exist!');
                $this->info('Run this command first:');
                $this->info('  php artisan empodat-suspect:refresh-filters --create');

                return Command::FAILURE;
            }

            // Determine which matrix types to process
            $matrixTypesToProcess = $this->getMatrixTypesToProcess();

            if (empty($matrixTypesToProcess)) {
                $this->error('✗ Invalid --only option. Valid values: '.implode(', ', array_keys($this->matrixTypes)));

                return Command::FAILURE;
            }

            $this->info('→ Processing '.count($matrixTypesToProcess).' matrix type(s)...');
            $this->newLine();

            $successCount = 0;
            $failCount = 0;

            foreach ($matrixTypesToProcess as $type => $mvName) {
                try {
                    $this->processMatrixType($type, $mvName);
                    $successCount++;
                } catch (\Exception $e) {
                    $this->error("  ✗ Failed to process {$type}: ".$e->getMessage());
                    $failCount++;
                }
            }

            $duration = round(microtime(true) - $startTime, 2);

            // Show statistics if requested
            if ($this->option('stats')) {
                $this->newLine();
                $this->showStatistics($matrixTypesToProcess);
            }

            $this->newLine();
            if ($failCount === 0) {
                $this->info("✓ All {$successCount} materialized views are ready! (completed in {$duration}s)");
            } else {
                $this->warn("⚠ Completed with issues: {$successCount} succeeded, {$failCount} failed (in {$duration}s)");
            }

            Log::info('Empodat Suspect matrix metadata refresh completed', [
                'duration' => $duration,
                'success_count' => $successCount,
                'fail_count' => $failCount,
            ]);

            return $failCount === 0 ? Command::SUCCESS : Command::FAILURE;

        } catch (\Exception $e) {
            $this->newLine();
            $this->error('✗ Failed to refresh materialized views:');
            $this->error('  '.$e->getMessage());

            if ($this->getOutput()->isVerbose()) {
                $this->newLine();
                $this->error($e->getTraceAsString());
            }

            Log::error('Empodat Suspect matrix metadata refresh failed: '.$e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return Command::FAILURE;
        }
    }

    /**
     * Check if the helper table exists (prerequisite)
     */
    private function checkHelperTableExists(): bool
    {
        $this->info('→ Checking prerequisites...');

        $result = DB::select("
            SELECT EXISTS (
                SELECT FROM information_schema.tables
                WHERE table_schema = 'public'
                AND table_name = 'empodat_suspect_stations_helper'
            ) as exists
        ");

        $exists = $result[0]->exists ?? false;

        if ($exists) {
            $count = DB::table('empodat_suspect_stations_helper')->count();
            $this->info("  ✓ Helper table exists ({$count} stations)");
        } else {
            $this->warn('  ✗ Helper table does not exist');
        }

        return $exists;
    }

    /**
     * Get matrix types to process based on --only option
     */
    private function getMatrixTypesToProcess(): array
    {
        $only = $this->option('only');

        if (! $only) {
            return $this->matrixTypes;
        }

        if (! isset($this->matrixTypes[$only])) {
            return [];
        }

        return [$only => $this->matrixTypes[$only]];
    }

    /**
     * Process a single matrix type (create or refresh)
     */
    private function processMatrixType(string $type, string $mvName): void
    {
        $viewExists = $this->checkViewExists($mvName);

        if ($this->option('create') || ! $viewExists) {
            $this->createMatrixView($type, $mvName);
        } else {
            $this->refreshMatrixView($type, $mvName);
        }
    }

    /**
     * Check if a specific MV exists
     */
    private function checkViewExists(string $mvName): bool
    {
        $result = DB::select("
            SELECT EXISTS (
                SELECT FROM pg_matviews
                WHERE schemaname = 'public'
                AND matviewname = ?
            ) as exists
        ", [$mvName]);

        return $result[0]->exists ?? false;
    }

    /**
     * Refresh an existing MV
     */
    private function refreshMatrixView(string $type, string $mvName): void
    {
        $concurrent = ! $this->option('force');

        if ($concurrent) {
            $this->line("  → Refreshing {$type} (CONCURRENT)...");
            $refreshStart = microtime(true);

            try {
                DB::statement("REFRESH MATERIALIZED VIEW CONCURRENTLY {$mvName}");
            } catch (\Exception $e) {
                // Concurrent refresh requires unique index, fall back to regular refresh
                $this->warn('    Concurrent refresh failed, using regular refresh...');
                DB::statement("REFRESH MATERIALIZED VIEW {$mvName}");
            }

            $duration = round(microtime(true) - $refreshStart, 2);
            $this->info("  ✓ {$type} refreshed ({$duration}s)");
        } else {
            $this->line("  → Refreshing {$type} (FORCE)...");
            $refreshStart = microtime(true);
            DB::statement("REFRESH MATERIALIZED VIEW {$mvName}");
            $duration = round(microtime(true) - $refreshStart, 2);
            $this->info("  ✓ {$type} refreshed ({$duration}s)");
        }
    }

    /**
     * Create a matrix MV from scratch
     */
    private function createMatrixView(string $type, string $mvName): void
    {
        $this->line("  → Creating {$type}...");
        $createStart = microtime(true);

        // Drop if exists
        DB::statement("DROP MATERIALIZED VIEW IF EXISTS {$mvName} CASCADE");

        // Create based on type
        $method = 'create'.str_replace('_', '', ucwords($type, '_')).'View';
        if (method_exists($this, $method)) {
            $this->$method($mvName);
        } else {
            throw new \RuntimeException("Unknown matrix type: {$type}");
        }

        $duration = round(microtime(true) - $createStart, 2);
        $this->info("  ✓ {$type} created ({$duration}s)");
    }

    private function createBiotaView(string $mvName): void
    {
        DB::statement("
            CREATE MATERIALIZED VIEW {$mvName} AS
            SELECT DISTINCT
                ss.station_id,
                em.id as empodat_main_id,
                mb.*
            FROM empodat_suspect_stations_helper ss
            INNER JOIN empodat_main em ON em.station_id = ss.station_id
            INNER JOIN empodat_matrix_biota mb ON mb.id = em.id
        ");
        DB::statement("CREATE INDEX idx_esmb_station_id ON {$mvName}(station_id)");
        DB::statement("CREATE INDEX idx_esmb_empodat_main_id ON {$mvName}(empodat_main_id)");
    }

    private function createSedimentsView(string $mvName): void
    {
        DB::statement("
            CREATE MATERIALIZED VIEW {$mvName} AS
            SELECT DISTINCT
                ss.station_id,
                em.id as empodat_main_id,
                ms.*
            FROM empodat_suspect_stations_helper ss
            INNER JOIN empodat_main em ON em.station_id = ss.station_id
            INNER JOIN empodat_matrix_sediments ms ON ms.id = em.id
        ");
        DB::statement("CREATE INDEX idx_esms_station_id ON {$mvName}(station_id)");
        DB::statement("CREATE INDEX idx_esms_empodat_main_id ON {$mvName}(empodat_main_id)");
    }

    private function createWaterSurfaceView(string $mvName): void
    {
        DB::statement("
            CREATE MATERIALIZED VIEW {$mvName} AS
            SELECT DISTINCT
                ss.station_id,
                em.id as empodat_main_id,
                mws.*
            FROM empodat_suspect_stations_helper ss
            INNER JOIN empodat_main em ON em.station_id = ss.station_id
            INNER JOIN empodat_matrix_water_surface mws ON mws.id = em.id
        ");
        DB::statement("CREATE INDEX idx_esmws_station_id ON {$mvName}(station_id)");
        DB::statement("CREATE INDEX idx_esmws_empodat_main_id ON {$mvName}(empodat_main_id)");
    }

    private function createWaterGroundView(string $mvName): void
    {
        DB::statement("
            CREATE MATERIALIZED VIEW {$mvName} AS
            SELECT DISTINCT
                ss.station_id,
                em.id as empodat_main_id,
                mwg.*
            FROM empodat_suspect_stations_helper ss
            INNER JOIN empodat_main em ON em.station_id = ss.station_id
            INNER JOIN empodat_matrix_water_ground mwg ON mwg.id = em.id
        ");
        DB::statement("CREATE INDEX idx_esmwg_station_id ON {$mvName}(station_id)");
        DB::statement("CREATE INDEX idx_esmwg_empodat_main_id ON {$mvName}(empodat_main_id)");
    }

    private function createWaterWasteView(string $mvName): void
    {
        DB::statement("
            CREATE MATERIALIZED VIEW {$mvName} AS
            SELECT DISTINCT
                ss.station_id,
                em.id as empodat_main_id,
                mww.*
            FROM empodat_suspect_stations_helper ss
            INNER JOIN empodat_main em ON em.station_id = ss.station_id
            INNER JOIN empodat_matrix_water_waste mww ON mww.id = em.id
        ");
        DB::statement("CREATE INDEX idx_esmww_station_id ON {$mvName}(station_id)");
        DB::statement("CREATE INDEX idx_esmww_empodat_main_id ON {$mvName}(empodat_main_id)");
    }

    private function createSuspendedMatterView(string $mvName): void
    {
        DB::statement("
            CREATE MATERIALIZED VIEW {$mvName} AS
            SELECT DISTINCT
                ss.station_id,
                em.id as empodat_main_id,
                msm.*
            FROM empodat_suspect_stations_helper ss
            INNER JOIN empodat_main em ON em.station_id = ss.station_id
            INNER JOIN empodat_matrix_suspended_matter msm ON msm.id = em.id
        ");
        DB::statement("CREATE INDEX idx_esmsm_station_id ON {$mvName}(station_id)");
        DB::statement("CREATE INDEX idx_esmsm_empodat_main_id ON {$mvName}(empodat_main_id)");
    }

    private function createSoilView(string $mvName): void
    {
        DB::statement("
            CREATE MATERIALIZED VIEW {$mvName} AS
            SELECT DISTINCT
                ss.station_id,
                em.id as empodat_main_id,
                mso.*
            FROM empodat_suspect_stations_helper ss
            INNER JOIN empodat_main em ON em.station_id = ss.station_id
            INNER JOIN empodat_matrix_soil mso ON mso.id = em.id
        ");
        DB::statement("CREATE INDEX idx_esmso_station_id ON {$mvName}(station_id)");
        DB::statement("CREATE INDEX idx_esmso_empodat_main_id ON {$mvName}(empodat_main_id)");
    }

    private function createAirView(string $mvName): void
    {
        DB::statement("
            CREATE MATERIALIZED VIEW {$mvName} AS
            SELECT DISTINCT
                ss.station_id,
                em.id as empodat_main_id,
                ma.*
            FROM empodat_suspect_stations_helper ss
            INNER JOIN empodat_main em ON em.station_id = ss.station_id
            INNER JOIN empodat_matrix_air ma ON ma.id = em.id
        ");
        DB::statement("CREATE INDEX idx_esma_station_id ON {$mvName}(station_id)");
        DB::statement("CREATE INDEX idx_esma_empodat_main_id ON {$mvName}(empodat_main_id)");
    }

    private function createSewageSludgeView(string $mvName): void
    {
        DB::statement("
            CREATE MATERIALIZED VIEW {$mvName} AS
            SELECT DISTINCT
                ss.station_id,
                em.id as empodat_main_id,
                mss.*
            FROM empodat_suspect_stations_helper ss
            INNER JOIN empodat_main em ON em.station_id = ss.station_id
            INNER JOIN empodat_matrix_sewage_sludge mss ON mss.id = em.id
        ");
        DB::statement("CREATE INDEX idx_esmss_station_id ON {$mvName}(station_id)");
        DB::statement("CREATE INDEX idx_esmss_empodat_main_id ON {$mvName}(empodat_main_id)");
    }

    /**
     * Show statistics for the MVs
     */
    private function showStatistics(array $matrixTypes): void
    {
        $this->info('╔════════════════════════════════════════╗');
        $this->info('║      Matrix Metadata Statistics        ║');
        $this->info('╚════════════════════════════════════════╝');

        $totalRows = 0;
        $totalStations = 0;

        foreach ($matrixTypes as $type => $mvName) {
            try {
                if (! $this->checkViewExists($mvName)) {
                    $this->line("  {$type}: NOT CREATED");

                    continue;
                }

                $rowCount = DB::table($mvName)->count();
                $stationCount = DB::table($mvName)->distinct('station_id')->count('station_id');

                $totalRows += $rowCount;
                $totalStations = max($totalStations, $stationCount);

                $sizeResult = DB::select('
                    SELECT pg_size_pretty(pg_total_relation_size(?)) as size
                ', [$mvName]);
                $size = $sizeResult[0]->size ?? 'N/A';

                $this->line("  {$type}:");
                $this->line('    Records: '.number_format($rowCount).' | Stations: '.number_format($stationCount)." | Size: {$size}");
            } catch (\Exception $e) {
                $this->line("  {$type}: Error - ".$e->getMessage());
            }
        }

        $this->newLine();
        $this->line('  Total records across all MVs: '.number_format($totalRows));
        $this->line('  Max unique stations: '.number_format($totalStations));
    }
}
