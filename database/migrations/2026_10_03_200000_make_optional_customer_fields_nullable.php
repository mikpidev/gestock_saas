<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach ([
            'customers_codactividad_foreign',
            'customers_tipodocumento_foreign',
            'customers_direccion_departamento_foreign',
        ] as $foreign) {
            try {
                Schema::table('customers', function (Blueprint $table) use ($foreign) {
                    $table->dropForeign($foreign);
                });
            } catch (\Throwable $e) {
                //
            }
        }

        Schema::table('customers', function (Blueprint $table) {
            if (Schema::hasColumn('customers', 'numDocumento')) {
                $table->string('numDocumento', 14)->nullable()->change();
            }
            if (Schema::hasColumn('customers', 'nrc')) {
                $table->string('nrc', 10)->nullable()->change();
            }
            if (Schema::hasColumn('customers', 'codActividad')) {
                $table->string('codActividad', 10)->nullable()->change();
            }
            if (Schema::hasColumn('customers', 'descActividad')) {
                $table->string('descActividad')->nullable()->change();
            }
            if (Schema::hasColumn('customers', 'nombreComercial')) {
                $table->string('nombreComercial')->nullable()->change();
            }
            if (Schema::hasColumn('customers', 'direccion_departamento')) {
                $table->string('direccion_departamento', 2)->nullable()->change();
            }
            if (Schema::hasColumn('customers', 'direccion_municipio')) {
                $table->string('direccion_municipio', 2)->nullable()->change();
            }
            if (Schema::hasColumn('customers', 'direccion_complemento')) {
                $table->string('direccion_complemento')->nullable()->change();
            }
            if (Schema::hasColumn('customers', 'telefono')) {
                $table->string('telefono', 15)->nullable()->change();
            }
            if (Schema::hasColumn('customers', 'correo')) {
                $table->string('correo')->nullable()->change();
            }
            if (Schema::hasColumn('customers', 'tipoDocumento')) {
                $table->string('tipoDocumento', 10)->nullable()->change();
            }
        });

        \Illuminate\Support\Facades\DB::table('customers')->where('tipoDocumento', '')->update(['tipoDocumento' => null]);
        \Illuminate\Support\Facades\DB::table('customers')->where('codActividad', '')->update(['codActividad' => null]);
        if (Schema::hasColumn('customers', 'direccion_departamento')) {
            \Illuminate\Support\Facades\DB::table('customers')->where('direccion_departamento', '')->update(['direccion_departamento' => null]);
        }

        foreach ([
            ['col' => 'codActividad', 'name' => 'customers_codactividad_foreign', 'table' => 'cod_actividad'],
            ['col' => 'tipoDocumento', 'name' => 'customers_tipodocumento_foreign', 'table' => 'tipo_documento'],
            ['col' => 'direccion_departamento', 'name' => 'customers_direccion_departamento_foreign', 'table' => 'departamentos'],
        ] as $fk) {
            if (! Schema::hasColumn('customers', $fk['col'])) {
                continue;
            }
            try {
                Schema::table('customers', function (Blueprint $table) use ($fk) {
                    $table->foreign($fk['col'], $fk['name'])
                        ->references('codigo')
                        ->on($fk['table'])
                        ->onUpdate('cascade')
                        ->onDelete('restrict');
                });
            } catch (\Throwable $e) {
                //
            }
        }
    }

    public function down(): void
    {
        //
    }
};
