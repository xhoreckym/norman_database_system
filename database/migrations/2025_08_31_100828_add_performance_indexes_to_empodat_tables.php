<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Use regular CREATE INDEX instead of CONCURRENTLY to avoid transaction issues in migrations
        // These indexes significantly improve Empodat search performance
        
        // 1. Composite index for common search patterns on empodat_main
        DB::statement('CREATE INDEX IF NOT EXISTS idx_empodat_main_search_combo ON empodat_main (matrix_id, substance_id, sampling_date_year)');
        
        // 2. Composite index for country-based searches (through stations)
        DB::statement('CREATE INDEX IF NOT EXISTS idx_empodat_stations_country ON empodat_stations (country_id, id)');
        
        // 3. Composite index for data source filters
        DB::statement('CREATE INDEX IF NOT EXISTS idx_empodat_data_sources_filters ON empodat_data_sources (type_data_source_id, laboratory1_id, organisation_id)');
        
        // 4. Index for analytical method searches with rating
        DB::statement('CREATE INDEX IF NOT EXISTS idx_empodat_analytical_methods_search ON empodat_analytical_methods (analytical_method_id, rating)');
        
        // 5. Covering index for main search fields with included columns (simplified for compatibility)
        DB::statement('CREATE INDEX IF NOT EXISTS idx_empodat_main_covering ON empodat_main (id, dct_analysis_id, matrix_id, substance_id, concentration_indicator_id, sampling_date_year)');
        
        // 6. Index for file associations by dct_analysis_id
        DB::statement('CREATE INDEX IF NOT EXISTS idx_empodat_main_file_dct ON empodat_main_file (dct_analysis_id, file_id)');
        
        // 7. Partial index for Norman relevant substances only
        DB::statement('CREATE INDEX IF NOT EXISTS idx_susdat_substances_norman_relevant ON susdat_substances (id) WHERE relevant_to_norman = 1');
        
        // 8. Index for substance categories join
        DB::statement('CREATE INDEX IF NOT EXISTS idx_susdat_substance_category_lookup ON susdat_substance_category (substance_id, category_id)');
        
        // 9. Index for substance sources join  
        DB::statement('CREATE INDEX IF NOT EXISTS idx_sle_substance_source_lookup ON sle_substance_source (substance_id, source_id)');
        
        // 10. Index for concentration indicator joins
        DB::statement('CREATE INDEX IF NOT EXISTS idx_empodat_main_concentration_indicator ON empodat_main (concentration_indicator_id)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop the indexes in reverse order
        DB::statement('DROP INDEX IF EXISTS idx_empodat_main_concentration_indicator');
        DB::statement('DROP INDEX IF EXISTS idx_sle_substance_source_lookup');
        DB::statement('DROP INDEX IF EXISTS idx_susdat_substance_category_lookup');
        DB::statement('DROP INDEX IF EXISTS idx_susdat_substances_norman_relevant');
        DB::statement('DROP INDEX IF EXISTS idx_empodat_main_file_dct');
        DB::statement('DROP INDEX IF EXISTS idx_empodat_main_covering');
        DB::statement('DROP INDEX IF EXISTS idx_empodat_analytical_methods_search');
        DB::statement('DROP INDEX IF EXISTS idx_empodat_data_sources_filters');
        DB::statement('DROP INDEX IF EXISTS idx_empodat_stations_country');
        DB::statement('DROP INDEX IF EXISTS idx_empodat_main_search_combo');
    }
};
