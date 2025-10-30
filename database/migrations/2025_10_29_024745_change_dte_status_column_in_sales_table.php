<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            // Cambiar la columna dte_status de enum a string
            $table->string('dte_status', 50)->default('PENDIENTE')->change();
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            // Opcional: volver a enum original si se necesita
            $table->enum('dte_status', ['paid', 'unpaid', 'partial'])->default('unpaid')->change();
        });
    }
};
