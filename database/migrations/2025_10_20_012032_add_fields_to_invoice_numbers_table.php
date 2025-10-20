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
        Schema::table('invoice_numbers', function (Blueprint $table) {
            $table->string('numero_control', 100)->after('number')->nullable();
            $table->string('codigo_generacion', 36)->after('numero_control')->nullable()->unique();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoice_numbers', function (Blueprint $table) {
            $table->dropColumn(['numero_control', 'codigo_generacion']);
        });
    }
};
