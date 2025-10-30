<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->renameColumn('payment_status', 'dte_status');
        });
    }
    
    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->renameColumn('dte_status', 'payment_status');
        });
    }
};
