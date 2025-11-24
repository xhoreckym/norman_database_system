<?php

namespace Database\Seeders\EmpodatSuspect;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EmpodatSuspectBiotaXlsxStationsMappingFillSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     *
     * This seeder fills the station_id, count, and ids columns in
     * empodat_suspect_xlsx_stations_mapping by matching xlsx_name
     * with empodat_stations.short_sample_code for BIOTA data (CONNECT pattern)
     */
    public function run(): void
    {
        $this->command->info('Filling empodat_suspect_xlsx_stations_mapping for BIOTA data (CONNECT pattern)...');

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
        -- Construct pattern from xlsx_name and match with short_sample_code (case-insensitive)
        LOWER(s.short_sample_code) = LOWER('CONNECT ' || REGEXP_REPLACE(
            SUBSTRING(LOWER(m.xlsx_name) FROM 'connect ([0-9]+)'),
            '^0+',
            ''
        ))
        AND (s.is_deprecated IS NULL OR s.is_deprecated = false)
    WHERE
        LOWER(m.xlsx_name) LIKE 'connect%'
    GROUP BY
        m.id
) AS subquery
WHERE m.id = subquery.mapping_id;
";

        $affectedRows = DB::update($updateQuery);
        $this->command->info("Successfully updated {$affectedRows} rows.");
    }
}
// php artisan db:seed --class=Database\\Seeders\\EmpodatSuspect\\EmpodatSuspectBiotaXlsxStationsMappingFillSeeder
