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
        if (Schema::hasTable('empodat_station_merge_log')) {
            return;
        }

        Schema::create('empodat_station_merge_log', function (Blueprint $table) {
            $table->id();
            $table->foreignId('deprecated_station_id')->references('id')->on('empodat_stations');
            $table->foreignId('canonical_station_id')->references('id')->on('empodat_stations');
            $table->string('merge_reason')->nullable()->default(null);
            $table->json('deprecated_data')->nullable()->default(null); // Backup original data
            $table->foreignId('merged_by')->nullable()->default(null)->references('id')->on('users');
            $table->timestamps();

            $table->index(['deprecated_station_id', 'canonical_station_id'], 'idx_station_merge_ids');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('empodat_station_merge_log');
    }
};
