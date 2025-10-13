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
        // List of life stages (e.g., adult, juvenile, hatchling, imago, larvae)
        Schema::create('list_life_stages', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        // List of habitat types (EUNIS Habitat type)
        Schema::create('list_habitat_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        // List of concentration units
        Schema::create('list_concentration_units', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        // List of latin names for species
        Schema::create('list_latin_names', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        // List of common names for species
        Schema::create('list_common_names', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('list_common_names');
        Schema::dropIfExists('list_latin_names');
        Schema::dropIfExists('list_concentration_units');
        Schema::dropIfExists('list_habitat_types');
        Schema::dropIfExists('list_life_stages');
    }
};
