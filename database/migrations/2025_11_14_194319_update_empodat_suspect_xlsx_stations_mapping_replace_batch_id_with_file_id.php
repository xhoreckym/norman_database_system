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
        Schema::table('empodat_suspect_xlsx_stations_mapping', function (Blueprint $table) {
            // Remove batch_id column
            $table->dropColumn('batch_id');

            // Add file_id column with foreign key
            $table->unsignedBigInteger('file_id')->nullable()->after('xlsx_name');
            $table->foreign('file_id')->references('id')->on('files')->onDelete('set null');
            $table->index('file_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('empodat_suspect_xlsx_stations_mapping', function (Blueprint $table) {
            // Remove file_id and its constraints
            $table->dropForeign(['file_id']);
            $table->dropIndex(['file_id']);
            $table->dropColumn('file_id');

            // Add back batch_id column
            $table->integer('batch_id')->default(0)->after('xlsx_name');
        });
    }
};
