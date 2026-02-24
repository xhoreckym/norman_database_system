<?php

namespace Database\Seeders\EmpodatSuspect;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EmpodatSuspectUbaHelcomXlsxStationsMappingFillSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     *
     * This seeder fills the station_id, count, and ids columns in
     * empodat_suspect_xlsx_stations_mapping by extracting the short_sample_code
     * pattern "UBA-HELCOM XX" from the xlsx_name and matching it against
     * empodat_stations.short_sample_code.
     *
     * xlsx_name example: "Pooled liver of Grey seal from Baltic Sea (UBA-HELCOM 11)_Kiel_Germany_..."
     * Extracted short_sample_code: "UBA-HELCOM 11"
     */
    public function run(): void
    {
        $this->command->info('Filling empodat_suspect_xlsx_stations_mapping for UBA-HELCOM data...');

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
        LOWER(s.short_sample_code) = LOWER(
            SUBSTRING(m.xlsx_name FROM '\\(([Uu][Bb][Aa]-[Hh][Ee][Ll][Cc][Oo][Mm]\\s+[^)]+)\\)')
        )
        AND (s.is_deprecated IS NULL OR s.is_deprecated = false)
    WHERE
        LOWER(m.xlsx_name) LIKE '%uba-helcom%'
    GROUP BY
        m.id
) AS subquery
WHERE m.id = subquery.mapping_id;
";

        $affectedRows = DB::update($updateQuery);
        $this->command->info("Successfully updated {$affectedRows} rows.");

        // Report any mappings that didn't get a station_id
        $unmapped = DB::table('empodat_suspect_xlsx_stations_mapping')
            ->whereRaw("LOWER(xlsx_name) LIKE '%uba-helcom%'")
            ->whereNull('station_id')
            ->count();

        if ($unmapped > 0) {
            $this->command->warn("{$unmapped} UBA-HELCOM mapping(s) could not be matched to a station.");
        }
    }
}
// php artisan db:seed --class=Database\\Seeders\\EmpodatSuspect\\EmpodatSuspectUbaHelcomXlsxStationsMappingFillSeeder
