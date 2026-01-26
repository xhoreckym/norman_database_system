<?php

declare(strict_types=1);

namespace Database\Seeders\Literature;

use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\RichText\RichText;

class LiteratureSeeder_TerraChemPOPs extends Seeder
{
    use WithoutModelEvents;

    // Lookup caches (lowercase key => id)
    protected array $speciesCache = [];
    protected array $tissuesCache = [];
    protected array $sexCache = [];
    protected array $lifeStagesCache = [];
    protected array $concentrationUnitsCache = [];
    protected array $commonNamesCache = [];
    protected array $matricesCache = [];
    protected array $substanceCache = [];
    protected array $countriesCache = [];

    // Test mode - set to null for full processing
    protected ?int $limitRows = null;
    protected int $fileId = 9002;

    // Excel structure constants
    protected int $headerRow = 2;
    protected int $dataStartRow = 3;
    protected int $lastDataColumn = 49; // Column AW

    // Skipped rows log
    protected array $skippedRows = [];
    protected string $skippedRowsLogPath;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Increase PHP memory limit and execution time FIRST
        ini_set('memory_limit', '16G');
        ini_set('max_execution_time', '7200'); // 2 hours
        $this->command->info('Memory limit set to 16GB, execution time to 2 hours');

        $target_table_name = 'literature_temp_main';

        $this->skippedRowsLogPath = base_path('database/seeders/seeds/literature/terrachem_pops_skipped_rows.log');

        // Delete only records for this file_id (preserves data from other seeders)
        $deleted = DB::table($target_table_name)->where('file_id', $this->fileId)->delete();
        $this->command->info("Deleted {$deleted} existing records for file_id = {$this->fileId}");

        $this->command->info('Loading lookup tables into cache...');
        $this->loadLookupCaches();

        // Disable Telescope during seeding to prevent memory issues
        if (class_exists(\Laravel\Telescope\Telescope::class)) {
            \Laravel\Telescope\Telescope::stopRecording();
            $this->command->info('Telescope recording stopped for memory optimization');
        }

        // Disable query logging for performance
        DB::connection()->disableQueryLog();

        // Disable foreign key checks temporarily for faster inserts (PostgreSQL)
        DB::statement('SET session_replication_role = replica;');

        $path = base_path('database/seeders/seeds/literature/DCT_BIOTA_TerraChem_LiteratureData_ POPs_19102025_v1.xlsx');

        if (! file_exists($path)) {
            $this->command->error("Excel file not found: {$path}");

            return;
        }

        if ($this->limitRows) {
            $this->command->warn("TEST MODE: Processing only first {$this->limitRows} rows");
        }

        $this->command->info('Loading Excel file (this may take a moment)...');

        // Load the spreadsheet
        $spreadsheet = IOFactory::load($path);
        $sheet = $spreadsheet->getActiveSheet();
        $highestRow = $sheet->getHighestRow();

        $this->command->info("Sheet has {$highestRow} rows, processing columns A-AW (1-{$this->lastDataColumn})");

        $batch = [];
        $batchSize = 500;
        $rowCount = 0;
        $skippedRowCount = 0;
        $progressInterval = 500;
        $startTime = microtime(true);
        $lastProgressTime = $startTime;
        $now = Carbon::now();

        // Start transaction for better performance
        DB::beginTransaction();

        try {
            $this->command->info("Processing rows {$this->dataStartRow} to {$highestRow}...");

            for ($rowIndex = $this->dataStartRow; $rowIndex <= $highestRow; $rowIndex++) {
                // Test mode row limit
                if ($this->limitRows && $rowCount >= $this->limitRows) {
                    break;
                }

                try {
                    // Read row data
                    $rowData = $this->readRow($sheet, $rowIndex);

                    // Sanity check before processing
                    $validationResult = $this->validateRow($rowData, $rowIndex);
                    if ($validationResult !== true) {
                        $this->logSkippedRow($rowIndex, $validationResult, $rowData);
                        $skippedRowCount++;

                        continue;
                    }

                    // Process row - one record per row (long format)
                    $record = $this->processRow($rowData, $rowIndex, $now);

                    if ($record) {
                        $batch[] = $record;
                        $rowCount++;

                        // Insert batch when it reaches the batch size
                        if (count($batch) >= $batchSize) {
                            DB::table($target_table_name)->insert($batch);
                            unset($batch);
                            $batch = [];
                        }
                    }

                    // Report progress
                    if ($rowCount % $progressInterval === 0) {
                        $currentTime = microtime(true);
                        $batchDuration = round($currentTime - $lastProgressTime, 2);
                        $totalDuration = round($currentTime - $startTime, 2);
                        $this->command->info("Processed {$rowCount} rows... (batch: {$batchDuration}s, total: {$totalDuration}s)");
                        $lastProgressTime = $currentTime;

                        // Force garbage collection periodically
                        gc_collect_cycles();
                    }
                } catch (\Exception $e) {
                    $this->logSkippedRow($rowIndex, 'Exception: '.$e->getMessage(), []);
                    $skippedRowCount++;

                    continue;
                }
            }

            // Insert remaining records
            if (! empty($batch)) {
                DB::table($target_table_name)->insert($batch);
                unset($batch);
                $batch = [];
            }

            DB::commit();

            // Clear memory
            $spreadsheet->disconnectWorksheets();
            unset($spreadsheet);
            gc_collect_cycles();
        } catch (\Exception $e) {
            DB::rollBack();

            throw $e;
        }

        // Write skipped rows log
        $this->writeSkippedRowsLog();

        $totalTime = round(microtime(true) - $startTime, 2);
        $avgPerRow = $rowCount > 0 ? round($totalTime / $rowCount * 1000, 2) : 0;
        $rowsPerSecond = $rowCount > 0 ? round($rowCount / $totalTime, 2) : 0;

        $this->command->info("Successfully seeded {$rowCount} records into {$target_table_name} in {$totalTime}s");
        $this->command->info("Performance: {$avgPerRow}ms/row ({$rowsPerSecond} rows/second)");

        if ($skippedRowCount > 0) {
            $this->command->warn("Skipped {$skippedRowCount} rows due to validation errors.");
            $this->command->warn("See log file: {$this->skippedRowsLogPath}");
        }

        // Re-enable foreign key checks (PostgreSQL)
        DB::statement('SET session_replication_role = default;');

        // Re-enable query logging
        DB::connection()->enableQueryLog();

        // Re-enable Telescope
        if (class_exists(\Laravel\Telescope\Telescope::class)) {
            \Laravel\Telescope\Telescope::startRecording();
            $this->command->info('Telescope recording re-enabled');
        }

        $this->command->info("All records seeded with file_id: {$this->fileId}");
    }

    /**
     * Load all lookup tables into memory for faster processing
     */
    protected function loadLookupCaches(): void
    {
        // Load species by name_latin with lowercase keys
        $species = DB::table('list_species')
            ->whereNotNull('name_latin')
            ->select('id', 'name_latin')
            ->get();
        foreach ($species as $s) {
            $this->speciesCache[$this->normalizeForLookup($s->name_latin)] = $s->id;
        }
        $this->command->info('Loaded '.count($this->speciesCache).' species');

        // Load tissues by name with lowercase keys
        $tissues = DB::table('list_tissues')
            ->select('id', 'name')
            ->get();
        foreach ($tissues as $t) {
            $this->tissuesCache[$this->normalizeForLookup($t->name)] = $t->id;
        }
        $this->command->info('Loaded '.count($this->tissuesCache).' tissues');

        // Load sex by name with lowercase keys
        $sexes = DB::table('list_biota_sexs')
            ->select('id', 'name')
            ->get();
        foreach ($sexes as $s) {
            $this->sexCache[$this->normalizeForLookup($s->name)] = $s->id;
        }
        $this->command->info('Loaded '.count($this->sexCache).' sex types');

        // Load life stages by name with lowercase keys
        $lifeStages = DB::table('list_life_stages')
            ->select('id', 'name')
            ->get();
        foreach ($lifeStages as $ls) {
            $this->lifeStagesCache[$this->normalizeForLookup($ls->name)] = $ls->id;
        }
        $this->command->info('Loaded '.count($this->lifeStagesCache).' life stages');

        // Load concentration units (use unit-specific normalization)
        $concentrationUnits = DB::table('list_concentration_units')
            ->select('id', 'name')
            ->get();
        foreach ($concentrationUnits as $cu) {
            $this->concentrationUnitsCache[$this->normalizeUnitForLookup($cu->name)] = $cu->id;
        }
        $this->command->info('Loaded '.count($this->concentrationUnitsCache).' concentration units');

        // Load common names by name with lowercase keys
        $commonNames = DB::table('list_common_names')
            ->select('id', 'name')
            ->get();
        foreach ($commonNames as $cn) {
            $this->commonNamesCache[$this->normalizeForLookup($cn->name)] = $cn->id;
        }
        $this->command->info('Loaded '.count($this->commonNamesCache).' common names');

        // Load matrices by name with lowercase keys
        $matrices = DB::table('list_matrices')
            ->select('id', 'name')
            ->get();
        foreach ($matrices as $m) {
            $this->matricesCache[$this->normalizeForLookup($m->name)] = $m->id;
        }
        $this->command->info('Loaded '.count($this->matricesCache).' matrices');

        // Load susdat_substances mapping: code => id
        $substances = DB::table('susdat_substances')
            ->whereNotNull('code')
            ->select('id', 'code')
            ->get();
        foreach ($substances as $s) {
            $this->substanceCache[$s->code] = $s->id;
        }
        $this->command->info('Loaded '.count($this->substanceCache).' substances');

        // Load countries by name with lowercase keys
        $countries = DB::table('list_countries')
            ->select('id', 'name')
            ->get();
        foreach ($countries as $c) {
            $this->countriesCache[$this->normalizeForLookup($c->name)] = $c->id;
        }
        $this->command->info('Loaded '.count($this->countriesCache).' countries');
    }

    /**
     * Read a single row from the Excel sheet
     */
    protected function readRow($sheet, int $rowIndex): array
    {
        $data = [];
        for ($col = 1; $col <= $this->lastDataColumn; $col++) {
            $letter = Coordinate::stringFromColumnIndex($col);
            $value = $sheet->getCell($letter.$rowIndex)->getValue();

            // Convert RichText objects to plain strings
            if ($value instanceof RichText) {
                $value = $value->getPlainText();
            }

            $data[$letter] = $value;
        }

        return $data;
    }

    /**
     * Validate a row before processing
     *
     * @return true|string True if valid, error message string if invalid
     */
    protected function validateRow(array $rowData, int $rowIndex): bool|string
    {
        // Check for completely empty rows
        $hasData = false;
        foreach ($rowData as $value) {
            if (! $this->isSkipValue($value)) {
                $hasData = true;

                break;
            }
        }

        if (! $hasData) {
            return 'Empty row';
        }

        // Column C must contain a Norman ID (string starting with NS) or be empty
        $normanId = $rowData['C'] ?? null;
        if (! $this->isSkipValue($normanId)) {
            // Must be a string
            if (! is_string($normanId)) {
                return 'Invalid Norman ID type: expected string, got '.gettype($normanId).' ('.$normanId.')';
            }
            // Must start with NS
            if (! str_starts_with(trim($normanId), 'NS')) {
                return 'Invalid Norman ID format: '.$normanId.' (must start with NS)';
            }
        }

        return true;
    }

    /**
     * Process a single row from Excel
     */
    protected function processRow(array $data, int $rowIndex, Carbon $now): ?array
    {
        // Get concentration unit for ww_conc_ng determination
        $concentrationUnit = $data['G'] ?? null;
        $isWetWeight = $this->isWetWeightUnit($concentrationUnit);

        // Determine concentration value handling
        $concentrationValue = $data['D'] ?? null;
        $reportedConcentration = null;
        $concentrationLevel = null;
        $wwConcNg = null;

        if (! $this->isSkipValue($concentrationValue)) {
            $strValue = trim((string) $concentrationValue);
            if (is_numeric($strValue)) {
                $reportedConcentration = $strValue;
                if ($isWetWeight) {
                    $wwConcNg = (float) $strValue;
                }
            } else {
                $concentrationLevel = $strValue;
            }
        }

        // Build notes from multiple columns
        $notesParts = [];
        if (! $this->isSkipValue($data['Z'] ?? null)) {
            $notesParts[] = $this->cleanString($data['Z']);
        }
        if (! $this->isSkipValue($data['W'] ?? null)) {
            $notesParts[] = 'Matrix note: '.$this->cleanString($data['W']);
        }
        $comment = ! empty($notesParts) ? implode(' | ', $notesParts) : null;

        return [
            // File reference
            'file_id' => $this->fileId,

            // Row identifier
            'rowid' => $rowIndex,

            // Substance information (Col C: Norman ID, Col B: chemical name)
            'substance_id' => $this->lookupSubstance($data['C'] ?? null),
            'chemical_name' => $this->cleanString($data['B'] ?? null),

            // Species information (Col AM: latin_name, Col AF: common name)
            'species_id' => $this->lookupSpecies(
                $data['AM'] ?? null,
                $data['AH'] ?? null,  // kingdom (Animalia)
                $data['AI'] ?? null   // class (Aves)
            ),
            'common_name_id' => $this->lookupCommonName($data['AF'] ?? null),

            // Bibliographic source (Col AS: reference)
            'title' => $this->cleanString($data['AS'] ?? null),

            // Biota information
            'sex_id' => $this->lookupSex($data['AL'] ?? null), // Col AL: sex/gender
            'diet_as_described_in_paper' => $this->cleanString($data['AO'] ?? null), // Col AO: guild, diet
            'trophic_level_as_described_in_paper' => $this->cleanString($data['AN'] ?? null), // Col AN: trophic level
            'life_stage_id' => $this->lookupLifeStage($data['AK'] ?? null), // Col AK: lifestage

            // Location information
            'country_id' => $this->lookupCountry($data['AQ'] ?? null), // Col AQ: country
            'region_city' => $this->cleanString($data['AR'] ?? null), // Col AR: region

            // Health and habitat
            'health_status' => $this->cleanString($data['X'] ?? null), // Col X: health effect
            'reported_distance_to_industry' => $this->cleanString($data['T'] ?? null), // Col T

            // Tissue and measurement
            'tissue_id' => $this->lookupTissue($data['U'] ?? null), // Col U: matrix (tissue)
            'matrix_id' => $this->lookupMatrix($data['V'] ?? null), // Col V: Biota-Terrestrial
            'analytical_method' => $this->cleanString($data['J'] ?? null), // Col J: analytical technique
            'storage_temp_c' => $this->cleanString($data['L'] ?? null), // Col L: method of sample storage

            // Detection limits
            'lod' => $this->cleanString($data['H'] ?? null), // Col H: LOD
            'loq' => $this->cleanString($data['I'] ?? null), // Col I: LOQ

            // Sample information
            'x_of_replicates' => $this->cleanInt($data['N'] ?? null), // Col N: number of samples/replicates
            'sd' => $this->cleanString($data['E'] ?? null), // Col E: standard deviation

            // Range information
            'range_min' => $this->extractRangeMin($data['F'] ?? null), // Col F: range (min-max)
            'range_max' => $this->extractRangeMax($data['F'] ?? null),

            // Concentration
            'concentration_units_id' => $this->lookupConcentrationUnit($data['G'] ?? null), // Col G: unit
            'reported_concentration' => $reportedConcentration,
            'concentrationlevel' => $concentrationLevel,
            'ww_conc_ng' => $wwConcNg,

            // Sampling dates
            'start_of_sampling_year' => $this->cleanString($data['O'] ?? null), // Col O: year
            'start_of_sampling_month' => $this->cleanString($data['P'] ?? null), // Col P: date/season

            // Coordinates
            'latitude_1' => $this->cleanString($data['Q'] ?? null), // Col Q: latitude
            'longitude_1' => $this->cleanString($data['R'] ?? null), // Col R: longitude

            // Phylogenetic data
            'kingdom' => $this->cleanString($data['AH'] ?? null), // Col AH: Animalia
            'class_phyl' => $this->cleanString($data['AI'] ?? null), // Col AI: Aves

            // Comments
            'comment' => $comment,

            'created_at' => $now,
            'updated_at' => $now,
        ];
    }

    // ==================== LOOKUP METHODS ====================

    protected function normalizeForLookup(?string $value): string
    {
        if ($value === null) {
            return '';
        }

        return strtolower(trim(preg_replace('/\s+/', ' ', $value)));
    }

    protected function normalizeUnitForLookup(?string $value): string
    {
        if ($value === null) {
            return '';
        }

        return strtolower(str_replace(' ', '', trim($value)));
    }

    protected function isWetWeightUnit(?string $name): bool
    {
        if ($name === null) {
            return false;
        }
        $normalized = $this->normalizeUnitForLookup($name);

        return $normalized === 'ng/gww' || str_contains($normalized, 'ww');
    }

    protected function lookupSubstance(?string $normanId): ?int
    {
        if ($this->isSkipValue($normanId)) {
            return null;
        }

        // Strip "NS" prefix to get the code
        $code = preg_replace('/^NS/', '', trim((string) $normanId));

        return $this->substanceCache[$code] ?? null;
    }

    protected function lookupSpecies(?string $latinName, ?string $kingdom = null, ?string $classPhy = null): ?int
    {
        if ($this->isSkipValue($latinName)) {
            return null;
        }
        $normalized = $this->normalizeForLookup($latinName);

        // Return from cache if found
        if (isset($this->speciesCache[$normalized])) {
            return $this->speciesCache[$normalized];
        }

        // Auto-create missing species
        $cleanLatinName = $this->cleanString($latinName);
        $cleanKingdom = $this->cleanString($kingdom);
        $cleanClass = $this->cleanString($classPhy);

        $newId = DB::table('list_species')->insertGetId([
            'name_latin' => $cleanLatinName,
            'name' => $cleanLatinName,
            'kingdom' => $cleanKingdom,
            'class' => $cleanClass,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Add to cache for future lookups
        $this->speciesCache[$normalized] = $newId;

        return $newId;
    }

    protected function lookupCommonName(?string $name): ?int
    {
        if ($this->isSkipValue($name)) {
            return null;
        }
        $normalized = $this->normalizeForLookup($name);

        return $this->commonNamesCache[$normalized] ?? null;
    }

    protected function lookupCountry(?string $name): ?int
    {
        if ($this->isSkipValue($name)) {
            return null;
        }
        $normalized = $this->normalizeForLookup($name);

        return $this->countriesCache[$normalized] ?? null;
    }

    protected function lookupTissue(?string $name): ?int
    {
        if ($this->isSkipValue($name)) {
            return null;
        }
        $normalized = $this->normalizeForLookup($name);

        if (isset($this->tissuesCache[$normalized])) {
            return $this->tissuesCache[$normalized];
        }

        // Mapping for common variations
        $mapping = [
            'egg' => 'eggs',
            'eggs' => 'eggs',
            'liver' => 'liver',
            'muscle' => 'muscle',
            'whole body' => 'whole body',
            'soft body' => 'soft body',
            'blood' => 'blood',
            'feather' => 'feather',
            'feathers' => 'feather',
            'brain' => 'brain',
            'fat' => 'fat',
            'kidney' => 'kidney',
        ];

        $mapped = $mapping[$normalized] ?? null;
        if ($mapped && isset($this->tissuesCache[$mapped])) {
            return $this->tissuesCache[$mapped];
        }

        return $this->tissuesCache['other'] ?? null;
    }

    protected function lookupSex(?string $name): ?int
    {
        if ($this->isSkipValue($name)) {
            return null;
        }
        $normalized = $this->normalizeForLookup($name);

        if (isset($this->sexCache[$normalized])) {
            return $this->sexCache[$normalized];
        }

        $mapping = [
            'm' => 'male',
            'f' => 'female',
            'male' => 'male',
            'female' => 'female',
            'mixed' => 'mixed',
            'unknown' => 'nr',
            'na' => 'nr',
            'n/a' => 'nr',
        ];

        $mapped = $mapping[$normalized] ?? null;
        if ($mapped && isset($this->sexCache[$mapped])) {
            return $this->sexCache[$mapped];
        }

        return $this->sexCache['nr'] ?? null;
    }

    protected function lookupLifeStage(?string $name): ?int
    {
        if ($this->isSkipValue($name)) {
            return null;
        }
        $normalized = $this->normalizeForLookup($name);

        if (isset($this->lifeStagesCache[$normalized])) {
            return $this->lifeStagesCache[$normalized];
        }

        $mapping = [
            'adult' => 'adult',
            'adults' => 'adult',
            'juvenile' => 'juvenile',
            'juveniles' => 'juvenile',
            'na' => 'na',
            'n/a' => 'na',
        ];

        $mapped = $mapping[$normalized] ?? null;
        if ($mapped && isset($this->lifeStagesCache[$mapped])) {
            return $this->lifeStagesCache[$mapped];
        }

        return $this->lifeStagesCache['no data'] ?? null;
    }

    protected function lookupConcentrationUnit(?string $name): ?int
    {
        if ($this->isSkipValue($name)) {
            return null;
        }
        $normalized = $this->normalizeUnitForLookup($name);

        if (isset($this->concentrationUnitsCache[$normalized])) {
            return $this->concentrationUnitsCache[$normalized];
        }

        return $this->concentrationUnitsCache['other'] ?? null;
    }

    protected function lookupMatrix(?string $name): ?int
    {
        if ($this->isSkipValue($name)) {
            return null;
        }
        $normalized = $this->normalizeForLookup($name);

        if (isset($this->matricesCache[$normalized])) {
            return $this->matricesCache[$normalized];
        }

        $mapping = [
            'biota-terrestrial' => 'biota - terrestrial',
            'biota terrestrial' => 'biota - terrestrial',
        ];

        $mapped = $mapping[$normalized] ?? null;
        if ($mapped && isset($this->matricesCache[$mapped])) {
            return $this->matricesCache[$mapped];
        }

        foreach ($this->matricesCache as $key => $id) {
            if (str_contains($normalized, 'terrestrial') && str_contains($key, 'terrestrial')) {
                return $id;
            }
        }

        return $this->matricesCache['biota - other'] ?? null;
    }

    // ==================== HELPER METHODS ====================

    protected function isSkipValue($value): bool
    {
        if ($value === null || $value === '') {
            return true;
        }
        $strValue = strtolower(trim((string) $value));

        return $strValue === '' || $strValue === 'na' || $strValue === 'n/a' || $strValue === '-';
    }

    protected function cleanString($value): ?string
    {
        if ($this->isSkipValue($value)) {
            return null;
        }

        return trim((string) $value);
    }

    protected function cleanInt($value): ?int
    {
        if ($this->isSkipValue($value)) {
            return null;
        }
        $cleaned = trim((string) $value);

        return is_numeric($cleaned) ? (int) $cleaned : null;
    }

    /**
     * Extract minimum value from range string like "0.5-1.2"
     */
    protected function extractRangeMin(?string $value): ?string
    {
        if ($this->isSkipValue($value)) {
            return null;
        }
        $strValue = trim((string) $value);
        if (preg_match('/^([\d.]+)\s*[-–]\s*[\d.]+$/', $strValue, $matches)) {
            return $matches[1];
        }

        return null;
    }

    /**
     * Extract maximum value from range string like "0.5-1.2"
     */
    protected function extractRangeMax(?string $value): ?string
    {
        if ($this->isSkipValue($value)) {
            return null;
        }
        $strValue = trim((string) $value);
        if (preg_match('/^[\d.]+\s*[-–]\s*([\d.]+)$/', $strValue, $matches)) {
            return $matches[1];
        }

        return null;
    }

    protected function logSkippedRow(int $rowIndex, string $reason, array $rowData): void
    {
        $this->skippedRows[] = [
            'row' => $rowIndex,
            'reason' => $reason,
            'data_sample' => array_slice($rowData, 0, 10),
        ];

        if (count($this->skippedRows) <= 10) {
            $this->command->warn("Row {$rowIndex}: {$reason}");
        }
    }

    protected function writeSkippedRowsLog(): void
    {
        if (empty($this->skippedRows)) {
            return;
        }

        $content = "TerraChem POPs Seeder - Skipped Rows Log\n";
        $content .= 'Generated: '.Carbon::now()->toDateTimeString()."\n";
        $content .= str_repeat('=', 80)."\n\n";

        foreach ($this->skippedRows as $entry) {
            $content .= "Row {$entry['row']}: {$entry['reason']}\n";
            if (! empty($entry['data_sample'])) {
                $content .= '  Sample data: '.json_encode($entry['data_sample'])."\n";
            }
            $content .= "\n";
        }

        file_put_contents($this->skippedRowsLogPath, $content);
    }
}
// php artisan db:seed --class=Database\\Seeders\\Literature\\LiteratureSeeder_TerraChemPOPs
