<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\DatabaseEntity;

class DatabaseEntityLiteratureModuleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DatabaseEntity::create([
            'name' => 'Literature',
            'description' => 'Literature module',
            'image_path' => 'fas fa-book', // task: fill suitable fontawesome icon like fas fa-book
            'code' => 'literature',
            'dashboard_route_name' => 'literature.home.index',
            'last_update' => null,
            'number_of_records' => 0,
            'has_templates' => false,
            'parent_id' => null,
            'is_public' => false,
        ]);
    }
}
// php artisan db:seed --class=DatabaseEntityLiteratureModuleSeeder