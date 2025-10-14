<?php

namespace Database\Seeders\Literature;

use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LiteratureSeeder_ULEI extends Seeder
{
    use WithoutModelEvents;

    // Lookup caches
    protected array $speciesCache = [];
    protected array $countriesCache = [];
    protected array $tissuesCache = [];
    protected array $sexCache = [];
    protected array $lifeStagesCache = [];
    protected array $habitatTypesCache = [];
    protected array $concentrationUnitsCache = [];
    protected array $commonNamesCache = [];
    protected array $useCategoriesCache = [];
    protected array $substanceCache = [];

    // Test mode - set to null for full processing
    protected ?int $limitRows = 1000;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $target_table_name = 'literature_temp_main';

        $this->command->info('Truncating literature_temp_main table...');
        DB::table($target_table_name)->truncate();

        $this->command->info('Loading lookup tables into cache...');
        $this->loadLookupCaches();

        $now = Carbon::now();
        $path = base_path() . '/database/seeders/seeds/literature/2025-6-20_ULEI_Wildlife_Exposure_data.csv';

        if (!file_exists($path)) {
            $this->command->error("CSV file not found: {$path}");
            return;
        }

        if ($this->limitRows) {
            $this->command->warn("TEST MODE: Processing only first {$this->limitRows} rows");
        }

        $this->command->info('Reading CSV file...');

        $handle = fopen($path, 'r');
        if (!$handle) {
            $this->command->error("Failed to open CSV file");
            return;
        }

        // Read header
        $header = fgetcsv($handle);
        if (!$header) {
            $this->command->error("Failed to read CSV header");
            fclose($handle);
            return;
        }

        // Clean header - remove BOM, trim spaces
        $header = array_map(function($h) {
            // Remove UTF-8 BOM if present
            $h = str_replace("\xEF\xBB\xBF", '', $h);
            return trim($h);
        }, $header);

        // Debug: Show first few header columns
        $this->command->info("CSV Header (first 10 columns): " . implode(', ', array_slice($header, 0, 10)));
        $this->command->info("Total columns in header: " . count($header));

        $batch = [];
        $batchSize = 100; // Reduced for PostgreSQL parameter limit (103 columns × 500 rows = 51,500 params)
        $rowCount = 0;
        $skippedRows = 0;

        while (($row = fgetcsv($handle)) !== false) {
            // Test mode row limit
            if ($this->limitRows && $rowCount >= $this->limitRows) {
                break;
            }

            // Combine header with row
            if (count($row) !== count($header)) {
                if ($skippedRows < 10) {
                    $this->command->warn("Row " . ($rowCount + $skippedRows + 1) . " column count mismatch: expected " . count($header) . ", got " . count($row));
                }
                $skippedRows++;
                continue;
            }

            $data = array_combine($header, $row);
            if ($data === false) {
                if ($skippedRows < 10) {
                    $this->command->error("Failed to combine header and row at line " . ($rowCount + $skippedRows + 1));
                }
                $skippedRows++;
                continue;
            }

            try {
                $processedData = $this->processRow($data, $now);
                if ($processedData) {
                    $batch[] = $processedData;
                    $rowCount++;
                }
            } catch (\Exception $e) {
                // Only show first 10 errors to avoid spam
                if ($skippedRows < 10) {
                    $this->command->error("Error processing row " . ($rowCount + $skippedRows + 1) . ": " . $e->getMessage());
                }
                $skippedRows++;
                continue;
            }

            // Insert batch when it reaches the batch size
            if (count($batch) >= $batchSize) {
                DB::table($target_table_name)->insert($batch);
                $this->command->info("Processed {$rowCount} rows...");
                $batch = [];
            }
        }

        // Insert remaining records
        if (!empty($batch)) {
            DB::table($target_table_name)->insert($batch);
        }

        fclose($handle);

        $this->command->info("Successfully seeded {$rowCount} records into {$target_table_name} table.");
        if ($skippedRows > 0) {
            $this->command->warn("Skipped {$skippedRows} rows due to errors.");
        }

        // Link all seeded records to file_id 477 (ULEI data source)
        $this->linkRecordsToFile(477);
    }

    /**
     * Link all seeded literature records to a specific file
     */
    protected function linkRecordsToFile(int $fileId): void
    {
        $this->command->info("Linking literature records to file_id {$fileId}...");

        // First, remove any existing links for this file to avoid duplicates
        DB::table('file_literature_temp_main')
            ->where('file_id', $fileId)
            ->delete();

        // Get all literature_temp_main IDs
        $literatureIds = DB::table('literature_temp_main')
            ->pluck('id')
            ->toArray();

        if (empty($literatureIds)) {
            $this->command->warn("No literature records found to link.");
            return;
        }

        $this->command->info("Found " . count($literatureIds) . " literature records to link.");

        // Create pivot records in batches
        $now = Carbon::now();
        $pivotRecords = [];
        $batchSize = 1000;

        foreach ($literatureIds as $literatureId) {
            $pivotRecords[] = [
                'file_id' => $fileId,
                'literature_temp_main_id' => $literatureId,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            // Insert batch when it reaches the batch size
            if (count($pivotRecords) >= $batchSize) {
                DB::table('file_literature_temp_main')->insert($pivotRecords);
                $pivotRecords = [];
            }
        }

        // Insert remaining records
        if (!empty($pivotRecords)) {
            DB::table('file_literature_temp_main')->insert($pivotRecords);
        }

        $this->command->info("Successfully linked " . count($literatureIds) . " records to file_id {$fileId}.");
    }

    /**
     * Load all lookup tables into memory for faster processing
     */
    protected function loadLookupCaches(): void
    {
        // Load species by name_latin
        $this->speciesCache = DB::table('list_species')
            ->whereNotNull('name_latin')
            ->pluck('id', 'name_latin')
            ->map(fn($id) => $id)
            ->toArray();
        $this->command->info("Loaded " . count($this->speciesCache) . " species");

        // Load countries by name
        $this->countriesCache = DB::table('list_countries')
            ->pluck('id', 'name')
            ->map(fn($id) => $id)
            ->toArray();
        $this->command->info("Loaded " . count($this->countriesCache) . " countries");

        // Load tissues by name
        $this->tissuesCache = DB::table('list_tissues')
            ->pluck('id', 'name')
            ->map(fn($id) => $id)
            ->toArray();
        $this->command->info("Loaded " . count($this->tissuesCache) . " tissues");

        // Load sex by name
        $this->sexCache = DB::table('list_biota_sexs')
            ->pluck('id', 'name')
            ->map(fn($id) => $id)
            ->toArray();
        $this->command->info("Loaded " . count($this->sexCache) . " sex types");

        // Load life stages by name
        $this->lifeStagesCache = DB::table('list_life_stages')
            ->pluck('id', 'name')
            ->map(fn($id) => $id)
            ->toArray();
        $this->command->info("Loaded " . count($this->lifeStagesCache) . " life stages");

        // Load habitat types by name
        $this->habitatTypesCache = DB::table('list_habitat_types')
            ->pluck('id', 'name')
            ->map(fn($id) => $id)
            ->toArray();
        $this->command->info("Loaded " . count($this->habitatTypesCache) . " habitat types");

        // Load concentration units by name
        $this->concentrationUnitsCache = DB::table('list_concentration_units')
            ->pluck('id', 'name')
            ->map(fn($id) => $id)
            ->toArray();
        $this->command->info("Loaded " . count($this->concentrationUnitsCache) . " concentration units");

        // Load common names by name
        $this->commonNamesCache = DB::table('list_common_names')
            ->pluck('id', 'name')
            ->map(fn($id) => $id)
            ->toArray();
        $this->command->info("Loaded " . count($this->commonNamesCache) . " common names");

        // Load use categories by name
        $this->useCategoriesCache = DB::table('list_use_categories')
            ->pluck('id', 'name')
            ->map(fn($id) => $id)
            ->toArray();
        $this->command->info("Loaded " . count($this->useCategoriesCache) . " use categories");

        // Load substance mapping from chemical_name to substance_id
        $this->loadSubstanceMapping();
    }

    /**
     * Load substance mapping from ULEI chemical names to SUSDAT substance IDs
     */
    protected function loadSubstanceMapping(): void
    {
        $mappingPath = base_path() . '/database/seeders/seeds/literature/ulei_susdat_compounds.csv';

        if (!file_exists($mappingPath)) {
            $this->command->warn("Substance mapping file not found: {$mappingPath}");
            $this->command->warn("Substance IDs will not be populated.");
            return;
        }

        // First, load susdat_substances by code
        $susdatByCode = DB::table('susdat_substances')
            ->whereNotNull('code')
            ->pluck('id', 'code')
            ->toArray();

        $this->command->info("Loaded " . count($susdatByCode) . " SUSDAT substances");

        // Now read the mapping CSV and create chemical_name -> substance_id mapping
        $handle = fopen($mappingPath, 'r');
        if (!$handle) {
            $this->command->error("Failed to open substance mapping file");
            return;
        }

        // Read and skip header
        $header = fgetcsv($handle);
        $mappingCount = 0;

        while (($row = fgetcsv($handle)) !== false) {
            // Skip empty rows
            if (count($row) < 4) {
                continue;
            }

            $chemicalName = isset($row[1]) ? strtolower(trim($row[1])) : null;
            $susdatId = isset($row[3]) ? trim($row[3]) : null;

            // Skip if either field is empty
            if (empty($chemicalName) || empty($susdatId)) {
                continue;
            }

            // Strip prefix from susdat_id (e.g., "NS00001776" -> "00001776")
            // The susdat_id has format like "NS00001776", but code field only has "00001776"
            $codeToLookup = preg_replace('/^[A-Z]+/', '', $susdatId);

            // Look up the substance ID from the code
            if (isset($susdatByCode[$codeToLookup])) {
                $this->substanceCache[$chemicalName] = $susdatByCode[$codeToLookup];
                $mappingCount++;
            }
        }

        fclose($handle);
        $this->command->info("Loaded " . $mappingCount . " chemical name to substance ID mappings");
    }

    /**
     * Safe array access with default value
     */
    protected function getValueOrNull(array $data, string $key): ?string
    {
        return $data[$key] ?? null;
    }

    /**
     * Process a single row from CSV
     */
    protected function processRow(array $data, Carbon $now): ?array
    {
        // Validate that we have the data array correctly
        if (!isset($data['rowid'])) {
            throw new \Exception("Missing 'rowid' key. Available keys: " . implode(', ', array_slice(array_keys($data), 0, 10)));
        }

        return [
            // Basic identifiers
            'rowid' => $this->cleanInt($data['rowid'] ?? null),
            'substance_id' => $this->lookupSubstance($data['chemical_name']),

            // Species information
            'species_id' => $this->lookupSpecies($data['latin_name']),
            'common_name_id' => $this->lookupCommonName($data['common_name']),

            // Bibliographic source
            'title' => $this->cleanString($data['title']),
            'first_author' => $this->cleanString($data['first_author']),
            'year' => $this->cleanInt($data['year']),
            'doi' => $this->cleanString($data['doi']),

            // Biota information
            'sex_id' => $this->lookupSex($data['sex']),
            'diet_as_described_in_paper' => $this->cleanString($data['diet_as_described_in_paper']),
            'trophic_level_as_described_in_paper' => $this->cleanString($data['trophic_level_as_described_in_paper']),
            'life_stage_id' => $this->lookupLifeStage($data['life_stage']),
            'age_in_days' => $this->cleanString($data['age_in_days']),
            'x_of_replicates' => $this->cleanInt($data['x_of_replicates']),

            // Monitoring and sampling information
            'type_of_monitoring' => $this->cleanString($data['type_of_monitoring']),
            'active_passive_sampling' => $this->cleanString($data['active_passive_sampling']),

            // Location information
            'country_id' => $this->lookupCountry($data['name_of_country']),
            'region_city' => $this->cleanString($data['region_city']),

            // Health and habitat
            'health_status' => $this->cleanString($data['health_status']),
            'habitat_type_id' => $this->lookupHabitatType($data['habitat_type']),
            'reported_distance_to_industry' => $this->cleanString($data['reported_distance_to_industry']),

            // Pesticide treatment information
            'last_pesticide_treatment' => $this->cleanString($data['last_pesticide_treatment']),
            'pesticide_used_in_treatment' => $this->cleanString($data['pesticide_used_in_treatment']),

            // Tissue and measurement
            'tissue_id' => $this->lookupTissue($data['tissue']),
            'basis_of_measurement' => $this->cleanString($data['basis_of_measurement']),
            'analytical_method' => $this->cleanString($data['analytical_method']),
            'storage_temp_c' => $this->cleanString($data['storage_temp_c']),

            // Detection limits
            'lod' => $this->cleanString($data['lod']),
            'lod_unit' => $this->cleanString($data['lod_unit']),
            'loq' => $this->cleanString($data['loq']),
            'loq_unit' => $this->cleanString($data['loq_unit']),

            // Sample information
            'pooled' => $this->cleanString($data['pooled']),
            'x_of_subsamples' => $this->cleanString($data['x_of_subsamples']),
            'sd' => $this->cleanString($data['sd']),
            'type_of_numeric_quantity' => $this->cleanString($data['type_of_numeric_quantity']),

            // Range information
            'range_min' => $this->cleanString($data['range_min']),
            'range_max' => $this->cleanString($data['range_max']),
            'reported_range_min' => $this->cleanString($data['reported_range_min']),
            'type_of_range_max' => $this->cleanString($data['type_of_range_max']),

            // Concentration
            'concentration_units_id' => $this->lookupConcentrationUnit($data['concentration_units']),
            'frequency_of_detection' => $this->cleanString($data['frequency_of_detection']),
            'raw_data_available' => $this->cleanString($data['raw_data_available']),

            // Comments and identifiers
            'comment' => $this->cleanString($data['comment']),
            'nest_field_if_not_dicernable' => $this->cleanString($data['nest_field_if_not_dicernable']),
            'chain_id_if_paper_has_chain' => $this->cleanString($data['chain_id_if_paper_has_chain']),

            // Sampling dates
            'start_of_sampling_day' => $this->cleanInt($data['start_of_sampling_day']),
            'start_of_sampling_month' => $this->cleanString($data['start_of_sampling_month']),
            'start_of_sampling_year' => $this->cleanString($data['start_of_sampling_year']),
            'end_of_sampling_day' => $this->cleanInt($data['end_of_sampling_day']),
            'end_of_sampling_month' => $this->cleanString($data['end_of_sampling_month']),
            'end_of_sampling_year' => $this->cleanString($data['end_of_sampling_year']),

            // Coordinates
            'imputed_coordinates' => $this->cleanString($data['imputed_coordinates']),
            'latitude_1' => $this->cleanString($data['latitude_1']),
            'latitude_2' => $this->cleanString($data['latitude_2']),
            'latitude_3' => $this->cleanString($data['latitude_3']),
            'latitude_4' => $this->cleanString($data['latitude_4']),
            'latitude_5' => $this->cleanString($data['latitude_5']),
            'latitude_6' => $this->cleanString($data['latitude_6']),
            'latitude_7' => $this->cleanString($data['latitude_7']),
            'latitude_8' => $this->cleanString($data['latitude_8']),
            'latitude_9' => $this->cleanString($data['latitude_9']),
            'latitude_10' => $this->cleanString($data['latitude_10']),
            'longitude_1' => $this->cleanString($data['longitude_1']),
            'longitude_2' => $this->cleanString($data['longitude_2']),
            'longitude_3' => $this->cleanString($data['longitude_3']),
            'longitude_4' => $this->cleanString($data['longitude_4']),
            'longitude_5' => $this->cleanString($data['longitude_5']),
            'longitude_6' => $this->cleanString($data['longitude_6']),
            'longitude_7' => $this->cleanString($data['longitude_7']),
            'longitude_8' => $this->cleanString($data['longitude_8']),
            'longitude_9' => $this->cleanString($data['longitude_9']),
            'longitude_10' => $this->cleanString($data['longitude_10']),

            // Habitat and species classification
            'habitat_class' => $this->cleanString($data['habitat_class']),
            'dietary_preference' => $this->cleanString($data['dietary_preference']),

            // IDs and measurements
            'individual_id' => $this->cleanInt($data['Individual_ID']),
            'unique_measurement' => $this->cleanString($data['unique_measurement']),
            'concentrationlevel' => $this->cleanString($data['concentrationlevel']),
            'sample_id' => $this->cleanString($data['Sample_ID']),
            'reported_concentration' => $this->cleanString($data['reported_concentration']),
            'freq_numeric' => $this->cleanDouble($data['freq_numeric']),
            'n_0' => $this->cleanDouble($data['N_0']),

            // Phylogenetic data
            'kingdom' => $this->cleanString($data['kingdom']),
            'phylum' => $this->cleanString($data['phylum']),
            'order' => $this->cleanString($data['order']),
            'genus' => $this->cleanString($data['genus']),
            'class_phyl' => $this->cleanString($data['class_phyl']),
            'source_trait' => $this->cleanString($data['source_trait']),

            // Chemical information
            'class' => $this->cleanString($data['Class']),
            'source_chem' => $this->cleanString($data['source_chem']),
            'use_chem_id' => $this->lookupUseCategory($data['use_chem']),
            'is_transformation_product' => $this->cleanString($data['Is.Transformation.product']),
            'parent' => $this->cleanString($data['Parent']),
            'is_group' => $this->cleanString($data['IS_group']),

            // Calculated concentrations
            'water_content' => $this->cleanDouble($data['water_content']),
            'ww_conc_ng' => $this->cleanDouble($data['ww_conc_ng']),
            'ww_lod_ng' => $this->cleanDouble($data['ww_lod_ng']),
            'ww_loq_ng' => $this->cleanDouble($data['ww_loq_ng']),
            'ww_sd_ng' => $this->cleanDouble($data['ww_sd_ng']),
            'imputed_lod' => $this->cleanDouble($data['imputed_lod']),
            'all_means_without_0' => $this->cleanDouble($data['All_Means_Without_0']),
            'all_means_with_0' => $this->cleanDouble($data['All_Means_With_0']),

            // Chemical name
            'chemical_name' => $this->cleanString($data['chemical_name']),

            'created_at' => $now,
            'updated_at' => $now,
        ];
    }

    // Lookup methods
    protected function lookupSpecies(?string $latinName): ?int
    {
        if (empty($latinName)) return null;
        $cleaned = strtolower(trim($latinName));
        foreach ($this->speciesCache as $name => $id) {
            if (strtolower($name) === $cleaned) {
                return $id;
            }
        }
        return null;
    }

    protected function lookupCountry(?string $name): ?int
    {
        if (empty($name)) return null;
        $cleaned = strtolower(trim($name));
        foreach ($this->countriesCache as $countryName => $id) {
            if (strtolower($countryName) === $cleaned) {
                return $id;
            }
        }
        return null;
    }

    protected function lookupTissue(?string $name): ?int
    {
        if (empty($name)) return null;
        $cleaned = strtolower(trim($name));
        foreach ($this->tissuesCache as $tissueName => $id) {
            if (strtolower($tissueName) === $cleaned) {
                return $id;
            }
        }
        return null;
    }

    protected function lookupSex(?string $name): ?int
    {
        if (empty($name)) return null;
        $cleaned = strtolower(trim($name));
        foreach ($this->sexCache as $sexName => $id) {
            if (strtolower($sexName) === $cleaned) {
                return $id;
            }
        }
        return null;
    }

    protected function lookupLifeStage(?string $name): ?int
    {
        if (empty($name)) return null;
        $cleaned = strtolower(trim($name));
        foreach ($this->lifeStagesCache as $stageName => $id) {
            if (strtolower($stageName) === $cleaned) {
                return $id;
            }
        }
        return null;
    }

    protected function lookupHabitatType(?string $name): ?int
    {
        if (empty($name)) return null;
        $cleaned = strtolower(trim($name));

        // Skip invalid values
        if ($cleaned === 'na' || $cleaned === 'no data') {
            return null;
        }

        // Try exact match first
        foreach ($this->habitatTypesCache as $habitatName => $id) {
            if (strtolower($habitatName) === $cleaned) {
                return $id;
            }
        }

        // Fuzzy matching using keyword mapping
        $mappings = [
            'Coastal habitats' => ['coast', 'beach', 'shore', 'estuary', 'lagoon'],
            'Forest and other wooded land' => ['forest', 'woodland', 'wood', 'tree', 'canopy', 'taiga'],
            'Grasslands and lands dominated by forbs, mosses or lichens' => ['grassland', 'meadow', 'prairie', 'steppe', 'pasture', 'moss', 'lichen', 'forb'],
            'Heathland, scrub and tundra' => ['heath', 'scrub', 'tundra', 'shrub', 'moor'],
            'Ice-associated marine habitats' => ['ice', 'arctic', 'antarctic', 'polar', 'glacier', 'pack ice'],
            'Inland habitats with no or little soil and mostly with sparse vegetation' => ['rock', 'cliff', 'quarry', 'scree', 'bare', 'sparse'],
            'Marine benthic habitats' => ['benthic', 'seabed', 'seafloor', 'marine sediment'],
            'Pelagic water column' => ['pelagic', 'open sea', 'ocean', 'offshore'],
            'Vegetated man-made habitats' => ['farm', 'agricultural', 'arable', 'crop', 'field', 'orchard', 'vineyard', 'garden', 'park', 'urban', 'suburban', 'hedgerow', 'cereal', 'maize', 'conventional', 'organic'],
            'Wetlands' => ['wetland', 'marsh', 'swamp', 'bog', 'fen', 'pond', 'lake', 'river', 'stream', 'fjord', 'aquatic'],
        ];

        // Check each mapping category
        foreach ($mappings as $category => $keywords) {
            foreach ($keywords as $keyword) {
                if (str_contains($cleaned, $keyword)) {
                    // Find the ID for this category
                    foreach ($this->habitatTypesCache as $habitatName => $id) {
                        if (strtolower($habitatName) === strtolower($category)) {
                            return $id;
                        }
                    }
                }
            }
        }

        // If no match found, try to find "Other" category
        foreach ($this->habitatTypesCache as $habitatName => $id) {
            if (strtolower($habitatName) === 'other') {
                return $id;
            }
        }

        // If still no match, return null
        return null;
    }

    protected function lookupConcentrationUnit(?string $name): ?int
    {
        if (empty($name)) return null;
        $cleaned = strtolower(trim($name));
        foreach ($this->concentrationUnitsCache as $unitName => $id) {
            if (strtolower($unitName) === $cleaned) {
                return $id;
            }
        }
        return null;
    }

    protected function lookupCommonName(?string $name): ?int
    {
        if (empty($name)) return null;
        $cleaned = strtolower(trim($name));
        foreach ($this->commonNamesCache as $commonName => $id) {
            if (strtolower($commonName) === $cleaned) {
                return $id;
            }
        }
        return null;
    }

    protected function lookupUseCategory(?string $name): ?int
    {
        if (empty($name)) return null;
        $cleaned = strtolower(trim($name));
        foreach ($this->useCategoriesCache as $categoryName => $id) {
            if (strtolower($categoryName) === $cleaned) {
                return $id;
            }
        }
        return null;
    }

    protected function lookupSubstance(?string $chemicalName): ?int
    {
        if (empty($chemicalName)) return null;
        $cleaned = strtolower(trim($chemicalName));

        // Direct lookup from the mapping cache
        return $this->substanceCache[$cleaned] ?? null;
    }

    // Data cleaning methods
    protected function cleanString(?string $value): ?string
    {
        if ($value === null || $value === '' || $value === 'NA') {
            return null;
        }
        $cleaned = trim($value);
        return $cleaned === '' || $cleaned === 'NA' ? null : $cleaned;
    }

    protected function cleanInt(?string $value): ?int
    {
        if ($value === null || $value === '' || $value === 'NA') {
            return null;
        }
        $cleaned = trim($value);
        if ($cleaned === '' || $cleaned === 'NA') {
            return null;
        }
        return is_numeric($cleaned) ? (int) $cleaned : null;
    }

    protected function cleanDouble(?string $value): ?float
    {
        if ($value === null || $value === '' || $value === 'NA') {
            return null;
        }
        $cleaned = trim($value);
        if ($cleaned === '' || $cleaned === 'NA') {
            return null;
        }
        return is_numeric($cleaned) ? (float) $cleaned : null;
    }
}
// php artisan db:seed --class=Database\\Seeders\\Literature\\LiteratureSeeder_ULEI
