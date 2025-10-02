<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('customers', function (Blueprint $table) {
            // Cambiar el nombre de la columna nit → numDocumento
            if (Schema::hasColumn('customers', 'nit')) {
                $table->renameColumn('nit', 'numDocumento');
            }

            // Agregar tipoDocumento (relacionado al catálogo tipo_documento)
            if (!Schema::hasColumn('customers', 'tipoDocumento')) {
                $table->string('tipoDocumento', 10)->nullable()->after('id');
            }
        });
    }

    public function down()
    {
        Schema::table('customers', function (Blueprint $table) {
            // 🔁 Revertir los cambios
            if (Schema::hasColumn('customers', 'numDocumento')) {
                $table->renameColumn('numDocumento', 'nit');
            }

            if (Schema::hasColumn('customers', 'tipoDocumento')) {
                $table->dropColumn('tipoDocumento');
            }
        });
    }
};
