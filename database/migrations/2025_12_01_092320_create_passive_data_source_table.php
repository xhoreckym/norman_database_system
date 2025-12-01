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
        Schema::create('passive_data_source', function (Blueprint $table) {
            $table->id();
            $table->string('org_name', 255)->nullable();
            $table->string('org_city', 255)->nullable();
            $table->string('org_country', 255)->nullable();
            $table->string('org_lab1_name', 255)->nullable();
            $table->string('org_lab1_city', 255)->nullable();
            $table->string('org_lab1_country', 255)->nullable();
            $table->string('org_lab2_name', 255)->nullable();
            $table->string('org_lab2_city', 255)->nullable();
            $table->string('org_lab2_country', 255)->nullable();
            $table->string('org_family_name', 255)->nullable();
            $table->string('org_first_name', 255)->nullable();
            $table->string('org_email', 255)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('passive_data_source');
    }
};
