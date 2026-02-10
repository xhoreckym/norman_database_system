<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Recreates empodat_suspect_main as a PostgreSQL partitioned table
     * to separate numeric concentration records (~4M) from non-numeric (~30M).
     */
    public function up(): void
    {
        // Step 1: Drop existing table
        DB::statement('DROP TABLE IF EXISTS empodat_suspect_main CASCADE');

        // Step 2: Create partitioned table
        DB::statement('
            CREATE TABLE empodat_suspect_main (
                id BIGSERIAL,
                is_numeric_concentration BOOLEAN NOT NULL,
                file_id BIGINT NULL,
                substance_id BIGINT NULL,
                xlsx_station_mapping_id BIGINT NULL,
                station_id BIGINT NULL,
                concentration DOUBLE PRECISION NULL,
                ip TEXT NULL,
                ip_max DOUBLE PRECISION NULL,
                based_on_hrms_library BOOLEAN NULL,
                units VARCHAR(255) NULL,
                PRIMARY KEY (id, is_numeric_concentration)
            ) PARTITION BY LIST (is_numeric_concentration)
        ');

        // Step 3: Create partitions
        DB::statement('
            CREATE TABLE empodat_suspect_main_numeric
                PARTITION OF empodat_suspect_main
                FOR VALUES IN (TRUE)
        ');

        DB::statement('
            CREATE TABLE empodat_suspect_main_nonnumeric
                PARTITION OF empodat_suspect_main
                FOR VALUES IN (FALSE)
        ');

        // Step 4: Create indexes on numeric partition
        DB::statement('CREATE INDEX idx_esm_numeric_station_id ON empodat_suspect_main_numeric (station_id)');
        DB::statement('CREATE INDEX idx_esm_numeric_substance_id ON empodat_suspect_main_numeric (substance_id)');
        DB::statement('CREATE INDEX idx_esm_numeric_ip_max ON empodat_suspect_main_numeric (ip_max)');
        DB::statement('CREATE INDEX idx_esm_numeric_file_id ON empodat_suspect_main_numeric (file_id)');

        // Step 5: Create indexes on non-numeric partition (minimal)
        DB::statement('CREATE INDEX idx_esm_nonnumeric_station_id ON empodat_suspect_main_nonnumeric (station_id)');
        DB::statement('CREATE INDEX idx_esm_nonnumeric_substance_id ON empodat_suspect_main_nonnumeric (substance_id)');

        // Step 6: Add foreign key constraints (on parent table)
        DB::statement('
            ALTER TABLE empodat_suspect_main
                ADD CONSTRAINT fk_esm_substance
                FOREIGN KEY (substance_id) REFERENCES susdat_substances(id)
        ');

        DB::statement('
            ALTER TABLE empodat_suspect_main
                ADD CONSTRAINT fk_esm_station
                FOREIGN KEY (station_id) REFERENCES empodat_stations(id)
        ');

        DB::statement('
            ALTER TABLE empodat_suspect_main
                ADD CONSTRAINT fk_esm_xlsx_mapping
                FOREIGN KEY (xlsx_station_mapping_id) REFERENCES empodat_suspect_xlsx_stations_mapping(id)
        ');

        DB::statement('
            ALTER TABLE empodat_suspect_main
                ADD CONSTRAINT fk_esm_file
                FOREIGN KEY (file_id) REFERENCES files(id)
        ');
    }

    /**
     * Reverse the migrations.
     *
     * Recreates the original non-partitioned table structure.
     */
    public function down(): void
    {
        // Drop partitioned table (cascades to partitions)
        DB::statement('DROP TABLE IF EXISTS empodat_suspect_main CASCADE');

        // Recreate original non-partitioned table
        Schema::create('empodat_suspect_main', function ($table) {
            $table->id();
            $table->foreignId('substance_id')->nullable()->default(null)->references('id')->on('susdat_substances')->index();
            $table->foreignId('xlsx_station_mapping_id')->nullable()->default(null)->references('id')->on('empodat_suspect_xlsx_stations_mapping');
            $table->foreignId('station_id')->nullable()->default(null)->references('id')->on('empodat_stations');
            $table->double('concentration')->nullable()->default(null);
            $table->text('ip')->nullable()->default(null)->comment('Identification point (can be semicolon-separated)');
            $table->double('ip_max')->nullable()->default(null)->comment('Identification confidence (0-1)');
            $table->boolean('based_on_hrms_library')->nullable()->default(null);
            $table->string('units')->nullable()->default(null);
            $table->foreignId('file_id')->nullable()->default(null)->references('id')->on('files');
            $table->index('station_id');
        });
    }
};
