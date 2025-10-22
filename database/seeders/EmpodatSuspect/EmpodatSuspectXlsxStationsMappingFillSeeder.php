<?php

namespace Database\Seeders\EmpodatSuspect;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EmpodatSuspectXlsxStationsMappingFillSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     *
     * This seeder fills the station_id, count, and ids columns in
     * empodat_suspect_xlsx_stations_mapping by matching xlsx_name
     * with empodat_stations.short_sample_code
     */
    public function run(): void
    {
        $this->command->info('Starting to fill empodat_suspect_xlsx_stations_mapping table...');

        // First, let's show what will be updated (for investigation)
        $this->command->info('========================================');
        $this->command->info('INVESTIGATION MODE - Showing matches:');
        $this->command->info('========================================');

        $investigationQuery = "
            SELECT
                m.id as mapping_id,
                m.xlsx_name,
                -- Construct the pattern to match (e.g., 'LIFE APEX 9' from 'LIFE APEX 09...')
                'LIFE APEX ' || REGEXP_REPLACE(
                    SUBSTRING(m.xlsx_name FROM 'LIFE APEX ([0-9]+)'),
                    '^0+',
                    ''
                ) as constructed_pattern,
                -- Extract the number for ordering
                CAST(REGEXP_REPLACE(
                    SUBSTRING(m.xlsx_name FROM 'LIFE APEX ([0-9]+)'),
                    '^0+',
                    ''
                ) AS INTEGER) as extracted_number,
                -- Count matching stations
                COUNT(s.id) as station_count,
                -- Get first station_id (ordered by id)
                MIN(s.id) as first_station_id,
                -- Concatenate all matching station ids
                STRING_AGG(s.id::text, ', ' ORDER BY s.id) as all_station_ids,
                -- Show matching stations for verification
                STRING_AGG(s.short_sample_code, ' | ' ORDER BY s.id) as matching_codes,
                STRING_AGG(s.name, ' | ' ORDER BY s.id) as matching_names
            FROM
                empodat_suspect_xlsx_stations_mapping m
            LEFT JOIN
                empodat_stations s ON
                -- Construct pattern from xlsx_name and match with short_sample_code
                s.short_sample_code = 'LIFE APEX ' || REGEXP_REPLACE(
                    SUBSTRING(m.xlsx_name FROM 'LIFE APEX ([0-9]+)'),
                    '^0+',
                    ''
                )
            WHERE
                m.xlsx_name LIKE 'LIFE APEX%'
            GROUP BY
                m.id, m.xlsx_name
            ORDER BY
                extracted_number, m.xlsx_name
        ";

        $results = DB::select($investigationQuery);

        $this->command->info(sprintf("Found %d xlsx_name entries to process", count($results)));
        $this->command->newLine();

        // Display results in a readable format
        foreach ($results as $result) {
            $this->command->info("Mapping ID: {$result->mapping_id}");
            $this->command->info("  XLSX Name: {$result->xlsx_name}");
            $this->command->info("  Constructed Pattern: {$result->constructed_pattern}");
            $this->command->info("  Station Count: {$result->station_count}");
            $this->command->info("  First Station ID: " . ($result->first_station_id ?? 'NULL'));
            $this->command->info("  All Station IDs: " . ($result->all_station_ids ?? 'NULL'));
            $this->command->info("  Matching Codes: " . ($result->matching_codes ?? 'NULL'));
            $this->command->info("  Matching Names: " . ($result->matching_names ?? 'NULL'));
            $this->command->newLine();
        }

        $this->command->info('========================================');
        $this->command->info('EXECUTING UPDATE QUERY');
        $this->command->info('========================================');

        $updateQuery = "
UPDATE empodat_suspect_xlsx_stations_mapping m
SET
    station_id = subquery.first_station_id,
    count = subquery.station_count,
    ids = subquery.all_station_ids,
    updated_at = NOW()
FROM (
    SELECT
        m.id as mapping_id,
        COUNT(s.id) as station_count,
        MIN(s.id) as first_station_id,
        STRING_AGG(s.id::text, ', ' ORDER BY s.id) as all_station_ids
    FROM
        empodat_suspect_xlsx_stations_mapping m
    LEFT JOIN
        empodat_stations s ON
        -- Construct pattern from xlsx_name and match with short_sample_code
        s.short_sample_code = 'LIFE APEX ' || REGEXP_REPLACE(
            SUBSTRING(m.xlsx_name FROM 'LIFE APEX ([0-9]+)'),
            '^0+',
            ''
        )
    WHERE
        m.xlsx_name LIKE 'LIFE APEX%'
    GROUP BY
        m.id
) AS subquery
WHERE m.id = subquery.mapping_id;
";

        $this->command->info('Executing UPDATE query...');
        $affectedRows = DB::update($updateQuery);
        $this->command->info("Successfully updated {$affectedRows} rows.");
        $this->command->newLine();

        $this->command->info('========================================');
        $this->command->info('Query Explanation:');
        $this->command->info('========================================');
        $this->command->info('1. Extracts number from xlsx_name: "LIFE APEX 09..." → "09"');
        $this->command->info('2. Removes leading zeros: "09" → "9"');
        $this->command->info('3. Constructs pattern: "LIFE APEX " + "9" = "LIFE APEX 9"');
        $this->command->info('4. Matches with empodat_stations.short_sample_code');
        $this->command->info('5. Counts matches and concatenates all station IDs');
        $this->command->newLine();

        $this->command->info('========================================');
        $this->command->info('Statistics Summary:');
        $this->command->info('========================================');

        // Summary statistics
        $totalMappings = count($results);
        $withMatches = collect($results)->filter(fn($r) => $r->station_count > 0)->count();
        $withoutMatches = collect($results)->filter(fn($r) => $r->station_count == 0)->count();
        $multipleMatches = collect($results)->filter(fn($r) => $r->station_count > 1)->count();

        $this->command->info("Total xlsx_name entries: {$totalMappings}");
        $this->command->info("Entries with matches: {$withMatches}");
        $this->command->info("Entries without matches: {$withoutMatches}");
        $this->command->info("Entries with multiple matches: {$multipleMatches}");

        if ($withoutMatches > 0) {
            $this->command->newLine();
            $this->command->warn("Entries without matches:");
            foreach ($results as $result) {
                if ($result->station_count == 0) {
                    $this->command->line("  - {$result->xlsx_name} (extracted number: {$result->extracted_number})");
                }
            }
        }

        $this->command->newLine();
        $this->command->info('Investigation complete. Review the output above before executing the UPDATE query manually.');
    }
}
// php artisan db:seed --class=Database\\Seeders\\EmpodatSuspect\\EmpodatSuspectXlsxStationsMappingFillSeeder
