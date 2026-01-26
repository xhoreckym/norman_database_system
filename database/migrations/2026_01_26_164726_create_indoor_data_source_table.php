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
        Schema::create('indoor_data_source', function (Blueprint $table) {
            $table->id('id_data');

            // Type of data source reference
            $table->unsignedSmallInteger('dts_id')->default(0);

            // Project and organisation info
            $table->string('title_project', 255)->default('');
            $table->string('organisation', 255)->default('');
            $table->string('email', 255)->default('');

            // Laboratory info
            $table->string('laboratory_name', 255)->default('');
            $table->string('laboratory_id', 255)->default('');

            // Literature references
            $table->text('literature1')->nullable();
            $table->text('literature2')->nullable();

            // Author
            $table->string('author', 255)->default('');

            $table->timestamps();

            // Index for foreign key lookup
            $table->index('dts_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('indoor_data_source');
    }
};
