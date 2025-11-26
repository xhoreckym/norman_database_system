<?php

namespace Database\Seeders\Literature;

use App\Models\Backend\File;
use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class LiteratureFileSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     *
     * This seeder creates/updates all File records for Literature data sources.
     * Must be run before any of the main data seeders.
     */
    public function run(): void
    {
        $this->command->info('Creating/updating File records for Literature data sources...');

        $files = [
            [
                'id' => 9000,
                'original_name' => '2025-6-20_ULEI_Wildlife_Exposure_data.csv',
                'name' => 'ULEI Wildlife Exposure Data',
                'description' => 'ULEI Wildlife Exposure data from literature sources',
                'file_path' => 'literature/2025-6-20_ULEI_Wildlife_Exposure_data.csv',
                'mime_type' => 'text/csv',
                'database_entity_id' => 17,
            ],
        ];

        $now = Carbon::now();
        $created = 0;
        $updated = 0;

        foreach ($files as $fileData) {
            $file = File::updateOrCreate(
                ['id' => $fileData['id']],
                array_merge($fileData, [
                    'uploaded_at' => $now,
                    'is_deleted' => false,
                ])
            );

            if ($file->wasRecentlyCreated) {
                $created++;
                $this->command->info("Created File ID {$file->id}: {$file->name}");
            } else {
                $updated++;
                $this->command->info("Updated File ID {$file->id}: {$file->name}");
            }
        }

        $this->command->newLine();
        $this->command->info("Summary: {$created} files created, {$updated} files updated");
        $this->command->info('All Literature File records are ready.');
    }
}
// php artisan db:seed --class=Database\\Seeders\\Literature\\LiteratureFileSeeder
