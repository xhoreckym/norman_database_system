<?php

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
        // Type of sample lookup table
        if (! Schema::hasTable('arbg_data_type_of_sample')) {
            Schema::create('arbg_data_type_of_sample', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('description')->nullable();
                $table->boolean('is_active')->default(true);
                $table->integer('ordering')->default(0);
                $table->timestamps();
            });

            $now = now();
            DB::table('arbg_data_type_of_sample')->insert([
                ['id' => 0, 'name' => 'Unknown', 'ordering' => 0, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
                ['id' => 1, 'name' => 'Filtered sample', 'ordering' => 1, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
                ['id' => 2, 'name' => 'Non-filtered sample', 'ordering' => 2, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
                ['id' => 3, 'name' => 'Sludge', 'ordering' => 3, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
                ['id' => 4, 'name' => 'Other', 'ordering' => 4, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ]);
        }

        // Targeted analysis lookup table
        if (! Schema::hasTable('arbg_data_targeted_analysis')) {
            Schema::create('arbg_data_targeted_analysis', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('description')->nullable();
                $table->boolean('is_active')->default(true);
                $table->integer('ordering')->default(0);
                $table->timestamps();
            });

            $now = now();
            DB::table('arbg_data_targeted_analysis')->insert([
                ['id' => 0, 'name' => 'No', 'ordering' => 0, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
                ['id' => 1, 'name' => 'Yes - single', 'ordering' => 1, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
                ['id' => 2, 'name' => 'Yes - multiplex', 'ordering' => 2, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
                ['id' => 3, 'name' => 'Yes - array', 'ordering' => 3, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
                ['id' => 4, 'name' => 'Yes - metagenomics', 'ordering' => 4, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
                ['id' => 5, 'name' => 'Yes - other', 'ordering' => 5, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ]);
        }

        // Non-targeted analysis lookup table
        if (! Schema::hasTable('arbg_data_non_targeted_analysis')) {
            Schema::create('arbg_data_non_targeted_analysis', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('description')->nullable();
                $table->boolean('is_active')->default(true);
                $table->integer('ordering')->default(0);
                $table->timestamps();
            });

            $now = now();
            DB::table('arbg_data_non_targeted_analysis')->insert([
                ['id' => 0, 'name' => 'No', 'ordering' => 0, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
                ['id' => 1, 'name' => 'Illumina', 'ordering' => 1, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
                ['id' => 2, 'name' => '454', 'ordering' => 2, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
                ['id' => 3, 'name' => 'Ion Torrent', 'ordering' => 3, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
                ['id' => 4, 'name' => 'Other', 'ordering' => 4, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('arbg_data_type_of_sample');
        Schema::dropIfExists('arbg_data_targeted_analysis');
        Schema::dropIfExists('arbg_data_non_targeted_analysis');
    }
};
