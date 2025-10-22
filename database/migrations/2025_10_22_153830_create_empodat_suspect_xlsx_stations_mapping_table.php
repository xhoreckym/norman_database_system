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
        if (Schema::hasTable('empodat_suspect_xlsx_stations_mapping')) {
            return;
        }

        Schema::create('empodat_suspect_xlsx_stations_mapping', function (Blueprint $table) {
            $table->id();
            $table->foreignId('station_id')->nullable()->default(null)->references('id')->on('empodat_stations');
            $table->text('xlsx_name')->nullable()->default(null);
            $table->integer('count')->nullable()->default(null);
            $table->string('ids')->nullable()->default(null);
            $table->smallInteger('batch_id')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('empodat_suspect_xlsx_stations_mapping');
    }
};
