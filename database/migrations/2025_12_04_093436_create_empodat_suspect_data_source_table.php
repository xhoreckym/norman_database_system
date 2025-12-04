<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('empodat_suspect_data_source', function (Blueprint $table) {
            $table->id();
            $table->foreignId('file_id')->constrained('files')->onDelete('cascade');
            $table->foreignId('source_data_id')->nullable()->constrained('list_type_data_sources')->onDelete('restrict');
            $table->foreignId('monitoring_type_id')->nullable()->constrained('list_type_monitorings')->onDelete('restrict');
            $table->foreignId('organisation_id')->nullable()->constrained('list_data_source_organisations')->onDelete('restrict');
            $table->foreignId('laboratory_id')->nullable()->constrained('list_data_source_laboratories')->onDelete('restrict');
            // $table->timestamps();

            $table->index('file_id');
        });

        // Create trigger function to ensure only files with database_entity_id = 18 can be linked
        DB::unprepared('
            CREATE OR REPLACE FUNCTION check_empodat_suspect_data_source_file()
            RETURNS TRIGGER AS $$
            BEGIN
                IF NOT EXISTS (
                    SELECT 1 FROM files
                    WHERE id = NEW.file_id
                    AND database_entity_id = 18
                ) THEN
                    RAISE EXCEPTION \'empodat_suspect_data_source.file_id must reference a file with database_entity_id = 18 (EMPODAT Suspect)\';
                END IF;
                RETURN NEW;
            END;
            $$ LANGUAGE plpgsql;
        ');

        // Create trigger on INSERT and UPDATE
        DB::unprepared('
            CREATE TRIGGER trg_check_empodat_suspect_data_source_file
            BEFORE INSERT OR UPDATE OF file_id ON empodat_suspect_data_source
            FOR EACH ROW
            EXECUTE FUNCTION check_empodat_suspect_data_source_file();
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop trigger first
        DB::unprepared('DROP TRIGGER IF EXISTS trg_check_empodat_suspect_data_source_file ON empodat_suspect_data_source;');

        // Drop trigger function
        DB::unprepared('DROP FUNCTION IF EXISTS check_empodat_suspect_data_source_file();');

        Schema::dropIfExists('empodat_suspect_data_source');
    }
};
