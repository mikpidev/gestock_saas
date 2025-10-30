<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('credit_notes', function (Blueprint $table) {
            $table->renameColumn('payment_status', 'dte_status');
            $table->string('dte_status', 50)->default('PENDIENTE')->change();

        });
    }
    
    public function down(): void
    {
        Schema::table('credit_notes', function (Blueprint $table) {
            $table->renameColumn('dte_status', 'payment_status');
            $table->enum('dte_status', ['paid', 'unpaid', 'partial'])->default('unpaid')->change();
        });
    }
};
