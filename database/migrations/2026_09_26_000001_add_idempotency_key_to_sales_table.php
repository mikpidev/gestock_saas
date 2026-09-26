<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->string('idempotency_key', 255)->nullable()->after('store_id');
            $table->unique(['store_id', 'idempotency_key'], 'sales_store_idempotency_unique');
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropUnique('sales_store_idempotency_unique');
            $table->dropColumn('idempotency_key');
        });
    }
};
