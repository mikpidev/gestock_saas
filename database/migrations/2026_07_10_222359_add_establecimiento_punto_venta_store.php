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
        //Agregar Establecimiento y punto de venta a la Tabla Store

        Schema::table('stores', function (Blueprint $table) {
            $table->string('establecimiento', 4)->nullable()->after('store_name');
            $table->string('punto_venta', 4)->nullable()->after('establecimiento');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->dropColumn('establecimiento');
            $table->dropColumn('punto_venta');
        });
    }
};
