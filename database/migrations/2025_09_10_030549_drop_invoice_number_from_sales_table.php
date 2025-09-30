<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            // Eliminar la columna invoice_number
            if (Schema::hasColumn('sales', 'invoice_number')) {
                $table->dropColumn('invoice_number');
            }
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            // Restaurar la columna si se revierte la migration
            $table->string('invoice_number')->nullable();
        });
    }
};
