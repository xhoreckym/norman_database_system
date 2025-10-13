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

        // List of species with phylogenetic information
        Schema::create('list_species', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable()->default(null);
            $table->string('name_latin')->nullable()->default(null);
            $table->string('kingdom')->nullable()->default(null);
            $table->string('phylum')->nullable()->default(null);
            $table->string('order')->nullable()->default(null);
            $table->string('class')->nullable()->default(null);
            $table->string('genus')->nullable()->default(null);
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
        Schema::dropIfExists('list_species');
        Schema::dropIfExists('list_concentration_units');
        Schema::dropIfExists('list_habitat_types');
        Schema::dropIfExists('list_life_stages');
    }
};
