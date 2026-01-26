<?php

declare(strict_types=1);

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class IndoorDataSourceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Starting Indoor Data Source seeding...');

        $targetTable = 'indoor_data_source';
        $now = Carbon::now();
        $startTime = microtime(true);
        $path = base_path('database/seeders/seeds/indoor_tables/indoor_data_source.csv');

        if (! file_exists($path)) {
            $this->command->error("File not found: {$path}");

            return;
        }

        // Clear existing data
        DB::table($targetTable)->delete();

        // Temporarily disable foreign key checks
        Schema::disableForeignKeyConstraints();

        // Open file and process
        $handle = fopen($path, 'r');
        if (! $handle) {
            $this->command->error("Cannot open file: {$path}");

            return;
        }

        $chunkSize = 500;
        $records = [];
        $totalCount = 0;

        while (($row = fgetcsv($handle)) !== false) {
            // Skip empty rows
            if (empty($row) || (count($row) === 1 && empty($row[0]))) {
                continue;
            }

            $records[] = [
                'id_data' => $this->parseInteger($row[0] ?? null),
                'dts_id' => $this->parseInteger($row[1] ?? null),
                'title_project' => $row[2] ?? '',
                'organisation' => $row[3] ?? '',
                'email' => $row[4] ?? '',
                'laboratory_name' => $row[5] ?? '',
                'laboratory_id' => $row[6] ?? '',
                'literature1' => $this->nullIfEmpty($row[7] ?? null),
                'literature2' => $this->nullIfEmpty($row[8] ?? null),
                'author' => $row[9] ?? '',
                'created_at' => $now,
                'updated_at' => $now,
            ];

            // Insert in chunks
            if (count($records) >= $chunkSize) {
                DB::table($targetTable)->insert($records);
                $totalCount += count($records);
                $this->command->info("Inserted {$totalCount} records...");
                $records = [];
            }
        }

        // Insert remaining records
        if (! empty($records)) {
            DB::table($targetTable)->insert($records);
            $totalCount += count($records);
        }

        fclose($handle);

        // Re-enable foreign key checks
        Schema::enableForeignKeyConstraints();

        $elapsed = round(microtime(true) - $startTime, 2);
        $this->command->info("Indoor Data Source seeding completed! {$totalCount} records in {$elapsed}s");
    }

    /**
     * Parse integer value, defaulting to 0 for empty/null
     */
    private function parseInteger(?string $value): int
    {
        if ($value === null || $value === '') {
            return 0;
        }

        return (int) $value;
    }

    /**
     * Return null if value is empty
     */
    private function nullIfEmpty(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return $value;
    }
}
// php artisan db:seed --class=IndoorDataSourceSeeder
