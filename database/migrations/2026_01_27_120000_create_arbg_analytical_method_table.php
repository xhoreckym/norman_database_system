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
        // Drop the old bacteria-specific method table if it exists
        Schema::dropIfExists('arbg_bacteria_method');

        // Create the new unified analytical method table
        Schema::create('arbg_analytical_method', function (Blueprint $table) {
            $table->unsignedInteger('method_id')->primary();
            $table->unsignedTinyInteger('type_of_sample_id')->default(0);
            $table->string('type_of_sample_other')->nullable();
            $table->string('volume_of_sample_used_for_dna_extraction')->nullable();
            $table->string('method_used_for_dna_extraction')->nullable();
            $table->unsignedTinyInteger('targeted_analysis_id')->default(0);
            $table->string('targeted_analysis_other')->nullable();
            $table->unsignedTinyInteger('non_targeted_analysis_id')->default(0);
            $table->string('non_targeted_analysis_other')->nullable();
            $table->string('analysis_of_pooled_dna_extracts')->nullable();
            $table->string('analysis_of_pooled_dna_extracts_specify')->nullable();
            $table->string('dna')->nullable();
            $table->string('limit_of_detection')->nullable();
            $table->string('limit_of_quantification')->nullable();
            $table->string('uncertainty_of_the_quantification')->nullable();
            $table->string('efficiency')->nullable();
            $table->string('sequencing_read_depth')->nullable();
            $table->unsignedTinyInteger('analytical_method_id')->default(0);
            $table->string('analytical_method_other')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->index('type_of_sample_id');
            $table->index('targeted_analysis_id');
            $table->index('non_targeted_analysis_id');
            $table->index('analytical_method_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('arbg_analytical_method');
    }
};
