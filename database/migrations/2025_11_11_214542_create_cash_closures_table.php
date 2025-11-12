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
        Schema::create('cash_closures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained();
            $table->foreignId('user_id')->nullable()->constrained();
        
            $table->unsignedBigInteger('from_sale_id')->nullable();
            $table->unsignedBigInteger('to_sale_id')->nullable();
        
            $table->integer('total_sales')->default(0);
            $table->integer('total_credit_notes')->default(0);
            $table->integer('total_debit_notes')->default(0);
        
            $table->decimal('amount_sales', 10, 2)->default(0);
            $table->decimal('amount_credit_notes', 10, 2)->default(0);
            $table->decimal('amount_debit_notes', 10, 2)->default(0);
        
            $table->decimal('total_cash', 10, 2)->default(0);
            $table->decimal('total_card', 10, 2)->default(0);
        
            $table->timestamps();
        });
        
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cash_closures');
    }
};
