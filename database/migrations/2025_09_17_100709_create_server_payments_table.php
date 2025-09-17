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
        Schema::create('server_payment_main', function (Blueprint $table) {
            $table->id();
            $table->date('period_start_date');
            $table->date('period_end_date');
            $table->enum('status', ['not_paid', 'pending', 'paid'])->default('not_paid');
            $table->decimal('amount_without_vat', 12, 2)->default(0);
            $table->string('variable_symbol', 64)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('server_payment_main');
    }
};
