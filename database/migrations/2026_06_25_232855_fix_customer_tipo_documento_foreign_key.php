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

            // Crear la nueva FK
            $table->foreign('tipoDocumento')
                ->references('codigo')
                ->on('tipo_documento_identificacion')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {

            $table->dropForeign('customers_tipodocumento_foreign');
        });

        Schema::table('customers', function (Blueprint $table) {

            $table->foreign('tipoDocumento')
                ->references('codigo')
                ->on('tipo_documento')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
        });
    }
};
