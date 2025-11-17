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
        Schema::dropIfExists('empodat_matrix_sediments');

        Schema::create('empodat_matrix_sediments', function (Blueprint $table) {
            // Primary key that matches empodat_main.id
            $table->id();

            $table->string('name', 255)->nullable();

            $table->string('basin_name', 255)->nullable();

            $table->string('km', 255)->nullable();

            $table->smallInteger('dpr_id')->nullable();

            $table->smallInteger('de_id')->nullable();

            $table->string('depth_m', 255)->nullable();

            $table->string('carbon', 255)->nullable();

            $table->smallInteger('df_id')->nullable();

            $table->string('df_other', 255)->nullable();

            $table->smallInteger('dcat_id')->nullable();

            $table->string('dcat_other', 255)->nullable();

            $table->smallInteger('dtbu_id')->nullable();

            $table->string('dtbu_other', 255)->nullable();

            $table->string('total_carbon', 255)->nullable();

            // Note: Foreign key constraints from legacy system:
            // dpr_id -> data_pressures
            // de_id -> data_depth
            // df_id -> data_fraction
            // dcat_id -> data_category
            // dtbu_id -> data_treatment_before_use
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('empodat_matrix_sediments');
    }
};
