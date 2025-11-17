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
        // Drop the table if it exists
        Schema::dropIfExists('empodat_matrix_air');

        Schema::create('empodat_matrix_air', function (Blueprint $table) {
            // Primary key that matches empodat_main.id
            $table->id();

            $table->string('temperature', 255)->nullable();

            $table->string('height_level', 255)->nullable();

            $table->string('barometric_pressure', 255)->nullable();

            $table->string('humidity', 255)->nullable();

            $table->string('wider_area', 255)->nullable();

            $table->string('sea_level', 255)->nullable();

            $table->string('wind_speed', 255)->nullable();

            $table->string('wind_direction', 255)->nullable();

            // Location - data_location
            $table->smallInteger('dloca_id')->nullable();

            // Flow rate
            $table->string('flow_rate', 255)->nullable();

            // Sampling method - data_smo
            $table->smallInteger('dsmo_id')->nullable();

            // Sampling collection device - data_scd
            $table->smallInteger('dscd_id')->nullable();

            // Sampling height above ground level
            $table->string('ground_level', 255)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('empodat_matrix_air');
    }
};
