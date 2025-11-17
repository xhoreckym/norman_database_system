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
        Schema::dropIfExists('empodat_matrix_suspended_matter');

        Schema::create('empodat_matrix_suspended_matter', function (Blueprint $table) {
            // Primary key that matches empodat_main.id
            $table->id();

            $table->string('name', 255)->nullable();

            $table->string('basin_name', 255)->nullable();

            $table->string('km', 255)->nullable();

            $table->smallInteger('dpr_id')->nullable();

            $table->smallInteger('de_id')->nullable();

            $table->string('depth_m', 255)->nullable();

            $table->string('spm', 255)->nullable();

            $table->string('spm_orig', 255)->nullable();

            $table->string('carbon', 255)->nullable();

            $table->string('carbon_orig', 255)->nullable();

            $table->string('distance', 255)->nullable();

            $table->smallInteger('end_east_west')->nullable();

            $table->string('end_longitude_d', 10)->nullable();

            $table->string('end_longitude_m', 10)->nullable();

            $table->string('end_longitude_s', 10)->nullable();

            $table->string('end_longitude_decimal', 20)->nullable();

            $table->smallInteger('end_north_south')->nullable();

            $table->string('end_latitude_d', 10)->nullable();

            $table->string('end_latitude_m', 10)->nullable();

            $table->string('end_latitude_s', 10)->nullable();

            $table->string('end_latitude_decimal', 20)->nullable();

            $table->smallInteger('df_id')->nullable();

            $table->string('df_other', 255)->nullable();

            $table->smallInteger('dsa_id')->nullable();

            $table->string('dsa_other', 255)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('empodat_matrix_suspended_matter');
    }
};
