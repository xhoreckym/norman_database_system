-- Recommended indexes for EMPODAT performance optimization

-- 1. Composite index for common search patterns
CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_empodat_main_search_combo 
ON empodat_main (matrix_id, substance_id, sampling_date_year);

-- 2. Index for country-based searches (through stations)
CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_empodat_stations_country 
ON empodat_stations (country_id, id);

-- 3. Index for data source filters
CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_empodat_data_sources_filters
ON empodat_data_sources (type_data_source_id, laboratory1_id, organisation_id);

-- 4. Index for analytical method searches
CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_empodat_analytical_methods_search
ON empodat_analytical_methods (analytical_method_id, rating);

-- 5. Covering index for main search fields
CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_empodat_main_covering
ON empodat_main (id, dct_analysis_id, matrix_id, substance_id, concentration_indicator_id)
INCLUDE (sampling_date_year, concentration_value);

-- 6. Index for file associations
CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_empodat_main_file_dct
ON empodat_main_file (dct_analysis_id, file_id);

-- 7. Partial index for Norman relevant substances only
CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_susdat_substances_norman_relevant
ON susdat_substances (id) WHERE relevant_to_norman = 1;

-- Check index creation progress
SELECT 
    pid,
    now() - pg_stat_activity.query_start AS duration,
    query 
FROM pg_stat_activity 
WHERE query LIKE '%CREATE INDEX%';
