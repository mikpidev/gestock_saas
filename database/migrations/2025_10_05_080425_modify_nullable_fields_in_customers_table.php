<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            // 🔹 Intentamos soltar las foreign keys si existen
            DB::statement('ALTER TABLE customers DROP FOREIGN KEY IF EXISTS customers_tipodocumento_foreign;');
            DB::statement('ALTER TABLE customers DROP FOREIGN KEY IF EXISTS customers_codactividad_foreign;');

            // 🔹 Modificamos los campos
            $table->string('tipoDocumento')->nullable()->change();
            $table->string('numDocumento')->nullable()->change();
            $table->string('nrc')->nullable()->change();
            $table->string('nombre')->nullable()->change();
            $table->string('nombreComercial')->nullable()->change();
            $table->string('codActividad')->nullable()->change();
            $table->string('descActividad')->nullable()->change();
            $table->string('direccion_complemento')->nullable()->change();
            $table->string('telefono')->nullable()->change();
            $table->string('correo')->nullable()->change();

            // 🔹 Volvemos a crear las foreign keys
            $table->foreign('tipoDocumento')->references('codigo')->on('cat_tipo_documentos');
            $table->foreign('codActividad')->references('codigo')->on('cat_actividades_economicas');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            DB::statement('ALTER TABLE customers DROP FOREIGN KEY IF EXISTS customers_tipodocumento_foreign;');
            DB::statement('ALTER TABLE customers DROP FOREIGN KEY IF EXISTS customers_codactividad_foreign;');

            $table->string('tipoDocumento')->nullable(false)->change();
            $table->string('numDocumento')->nullable(false)->change();
            $table->string('nrc')->nullable(false)->change();
            $table->string('nombre')->nullable(false)->change();
            $table->string('nombreComercial')->nullable(false)->change();
            $table->string('codActividad')->nullable(false)->change();
            $table->string('descActividad')->nullable(false)->change();
            $table->string('direccion_complemento')->nullable(false)->change();
            $table->string('telefono')->nullable(false)->change();
            $table->string('correo')->nullable(false)->change();

            $table->foreign('tipoDocumento')->references('codigo')->on('cat_tipo_documentos');
            $table->foreign('codActividad')->references('codigo')->on('cat_actividades_economicas');
        });
    }
};
