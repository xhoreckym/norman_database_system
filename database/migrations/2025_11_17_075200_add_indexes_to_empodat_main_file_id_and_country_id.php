<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('empodat_main', function (Blueprint $table) {
            // Add indexes for better query performance
            // These columns are frequently used in WHERE clauses
            $table->index('file_id', 'empodat_main_file_id_index');
            $table->index('country_id', 'empodat_main_country_id_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('empodat_main', function (Blueprint $table) {
            // Drop the indexes
            $table->dropIndex('empodat_main_file_id_index');
            $table->dropIndex('empodat_main_country_id_index');
        });
    }
};
