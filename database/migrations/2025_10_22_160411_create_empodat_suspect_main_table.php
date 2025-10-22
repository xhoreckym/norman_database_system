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
        if (Schema::hasTable('empodat_suspect_main')) {
            return;
        }

        Schema::create('empodat_suspect_main', function (Blueprint $table) {
            $table->id();

            // Compound information
            $table->foreignId('substance_id')->nullable()->default(null)->references('id')->on('susdat_substances')->index();

            // Station/Sample information
            $table->foreignId('xlsx_station_mapping_id')->nullable()->default(null)->references('id')->on('empodat_suspect_xlsx_stations_mapping');
            $table->foreignId('station_id')->nullable()->default(null)->references('id')->on('empodat_stations');

            // Measurement data
            $table->double('concentration')->nullable()->default(null);
            $table->text('ip')->nullable()->default(null)->comment('Identification point (can be semicolon-separated)');
            $table->double('ip_max')->nullable()->default(null)->comment('Identification confidence (0-1)');
            $table->boolean('based_on_hrms_library')->nullable()->default(null);
            $table->string('units')->nullable()->default(null);

            $table->timestamps();

            // Indexes for common queries
            $table->index('station_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('empodat_suspect_main');
    }
};
