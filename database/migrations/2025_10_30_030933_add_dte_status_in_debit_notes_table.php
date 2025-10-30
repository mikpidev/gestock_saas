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
        Schema::table('debit_notes', function (Blueprint $table) {
            // Opción 1: agregar nueva columna después de total_iva
            $table->string('dte_status', 50)->default('PENDIENTE')->after('total_iva');

            // Si deseas renombrar la columna payment_status a otra cosa, hazlo por separado:
            // $table->renameColumn('payment_status', 'old_payment_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('debit_notes', function (Blueprint $table) {
            $table->dropColumn('dte_status');

            // Si habías renombrado payment_status, aquí lo puedes revertir
            // $table->renameColumn('old_payment_status', 'payment_status');
        });
    }
};
