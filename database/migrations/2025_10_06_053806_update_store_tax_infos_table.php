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
        Schema::table('store_tax_infos', function (Blueprint $table) {
            // Renombrar campo ncr → nrc
            if (Schema::hasColumn('store_tax_infos', 'ncr')) {
                $table->renameColumn('ncr', 'nrc');
            }

            // ➕ Agregar nuevos campos si aún no existen
            if (!Schema::hasColumn('store_tax_infos', 'direccion_departamento')) {
                $table->unsignedBigInteger('direccion_departamento')->after('direccion_fiscal');
            }

            if (!Schema::hasColumn('store_tax_infos', 'direccion_municipio')) {
                $table->unsignedBigInteger('direccion_municipio')->after('direccion_departamento');
            }

            if (!Schema::hasColumn('store_tax_infos', 'codActividad')) {
                $table->string('codActividad', 10)->after('direccion_municipio');
            }

            // Agregar llaves foráneas
            $table->foreign('codActividad')
                ->references('codigo')
                ->on('cod_actividad');

            $table->foreign('direccion_departamento')
                ->references('id')
                ->on('departamentos');

            $table->foreign('direccion_municipio')
                ->references('id')
                ->on('municipios');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('store_tax_infos', function (Blueprint $table) {
            // Quitar llaves foráneas
            $table->dropForeign(['codActividad']);
            $table->dropForeign(['direccion_departamento']);
            $table->dropForeign(['direccion_municipio']);

            // Eliminar columnas agregadas
            if (Schema::hasColumn('store_tax_infos', 'codActividad')) {
                $table->dropColumn('codActividad');
            }
            if (Schema::hasColumn('store_tax_infos', 'direccion_departamento')) {
                $table->dropColumn('direccion_departamento');
            }
            if (Schema::hasColumn('store_tax_infos', 'direccion_municipio')) {
                $table->dropColumn('direccion_municipio');
            }

            // Renombrar campo nrc → ncr
            if (Schema::hasColumn('store_tax_infos', 'nrc')) {
                $table->renameColumn('nrc', 'ncr');
            }
        });
    }
};
