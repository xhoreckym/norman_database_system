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
        Schema::dropIfExists('empodat_matrix_sewage_sludge');

        Schema::create('empodat_matrix_sewage_sludge', function (Blueprint $table) {
            // Primary key that matches empodat_main.id
            $table->id();

            $table->string('basin_name', 255)->nullable();

            $table->smallInteger('dpr_id')->nullable();

            // data_depth
            $table->smallInteger('de_id')->nullable();

            $table->string('depth_m', 255)->nullable();

            $table->string('ph', 255)->nullable();

            $table->string('temperature', 255)->nullable();

            $table->string('carbon', 255)->nullable();

            $table->string('type_industry', 255)->nullable();

            $table->string('capacity', 255)->nullable();

            // data_treatment_plant
            $table->smallInteger('dtp_id')->nullable();

            $table->string('dtp_other', 255)->nullable();

            // data_tertiary_treatment
            $table->smallInteger('dtt_id')->nullable();

            $table->string('dtt_other', 255)->nullable();

            $table->string('srt', 255)->nullable();

            $table->string('reactor', 255)->nullable();

            $table->string('description_sampling', 255)->nullable();

            // data_fraction
            $table->smallInteger('df_id')->nullable();

            $table->string('df_other', 255)->nullable();

            // data_sewage_sludge
            $table->smallInteger('dss_id')->nullable();

            $table->string('dss_other', 255)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('empodat_matrix_sewage_sludge');
    }
};
