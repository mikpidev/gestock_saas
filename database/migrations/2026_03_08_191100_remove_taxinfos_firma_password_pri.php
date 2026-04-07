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
        //removed cert_firma_digital y password_pri from taxinfos table
        Schema::table('store_tax_infos', function (Blueprint $table) {
            $table->dropColumn('cert_firma_digital');
            $table->dropColumn('password_pri');
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
