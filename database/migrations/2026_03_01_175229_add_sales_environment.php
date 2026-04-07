<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations. add to tables sales enum environment with values production, 
     */
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->enum('environment', ['Production', 'Development'])->default('Production')->nullable()->after('dte_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn('environment');
        });
    }
};
