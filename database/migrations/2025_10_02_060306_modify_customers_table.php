<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('customers', function (Blueprint $table) {

            // Agregar foreign keys seguras
            $table->foreign('tipoDocumento')
                  ->references('codigo')
                  ->on('tipo_documento')
                  ->onUpdate('cascade')
                  ->onDelete('restrict');

            $table->foreign('codActividad')
                  ->references('codigo')
                  ->on('cod_actividad')
                  ->onUpdate('cascade')
                  ->onDelete('restrict');

            $table->foreign('direccion_departamento')
                  ->references('codigo')
                  ->on('departamentos')
                  ->onUpdate('cascade')
                  ->onDelete('restrict');

            // direccion_municipio no FK
        });
    }

    public function down()
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropForeign(['tipoDocumento']);
            $table->dropForeign(['codActividad']);
            $table->dropForeign(['direccion_departamento']);
        });
    }
};
