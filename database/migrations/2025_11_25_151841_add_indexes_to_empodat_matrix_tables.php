<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $tables = [
            'empodat_matrix_air',
            'empodat_matrix_biota',
            'empodat_matrix_sediments',
            'empodat_matrix_sewage_sludge',
            'empodat_matrix_soil',
            'empodat_matrix_suspended_matter',
            'empodat_matrix_water_surface',
            'empodat_matrix_water_ground',
            'empodat_matrix_water_waste',
        ];

        foreach ($tables as $tableName) {
            if (Schema::hasTable($tableName)) {
                // Check if primary key already exists
                $hasPrimaryKey = DB::select("
                    SELECT 1 FROM information_schema.table_constraints
                    WHERE table_name = ? AND constraint_type = 'PRIMARY KEY'
                ", [$tableName]);

                if (empty($hasPrimaryKey)) {
                    DB::statement("ALTER TABLE \"{$tableName}\" ADD PRIMARY KEY (\"id\")");
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Don't drop primary keys on rollback - they should exist
    }
};
