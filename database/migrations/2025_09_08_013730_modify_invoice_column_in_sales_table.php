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
        Schema::table('sales', function (Blueprint $table) {
            // ejemplo: cambiar a foreignId para relacionar con invoice_numbers
            $table->foreignId('invoice_number_id')->after('customer_id')->nullable()->constrained('invoice_numbers');
            $table->dropColumn('invoice'); // si quieres eliminar la columna anterior
        });
    }
    
    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->string('invoice')->after('customer_id');
            $table->dropForeign(['invoice_number_id']);
            $table->dropColumn('invoice_number_id');
        });
    }
    
};
