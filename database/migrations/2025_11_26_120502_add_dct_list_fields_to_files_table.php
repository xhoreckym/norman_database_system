<?php

declare(strict_types=1);

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
        Schema::table('files', function (Blueprint $table) {
            // DOI field
            $table->string('doi')->nullable();

            // Main ID range (from *_main tables like empodat_main, indoor_main)
            $table->bigInteger('main_id_from')->nullable();
            $table->bigInteger('main_id_to')->nullable();
            $table->bigInteger('analysis_number')->nullable();

            // Source ID range
            $table->bigInteger('source_id_from')->nullable();
            $table->bigInteger('source_id_to')->nullable();
            $table->bigInteger('source_number')->nullable();

            // Method ID range
            $table->bigInteger('method_id_from')->nullable();
            $table->bigInteger('method_id_to')->nullable();
            $table->bigInteger('method_number')->nullable();

            // List type and note
            $table->string('list_type')->nullable();
            $table->text('note')->nullable();

            // Matrice DCT
            $table->bigInteger('matrice_dct')->nullable();

            // Add indexes for frequently queried columns
            $table->index('main_id_from');
            $table->index('main_id_to');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('files', function (Blueprint $table) {
            $table->dropIndex(['main_id_from']);
            $table->dropIndex(['main_id_to']);

            $table->dropColumn([
                'doi',
                'main_id_from',
                'main_id_to',
                'analysis_number',
                'source_id_from',
                'source_id_to',
                'source_number',
                'method_id_from',
                'method_id_to',
                'method_number',
                'list_type',
                'note',
                'matrice_dct',
            ]);
        });
    }
};
