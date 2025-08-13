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
        Schema::table('suspect_list_exchange_sources', function (Blueprint $table) {
            $table->text('link_full_list')->nullable();
            $table->text('link_inchikey_list')->nullable();
            $table->text('link_references')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('suspect_list_exchange_sources', function (Blueprint $table) {
            $table->dropColumn(['link_full_list', 'link_inchikey_list', 'link_references']);
        });
    }
};
