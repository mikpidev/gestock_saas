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
        Schema::table('customers', function (Blueprint $table) {
            $table->foreignId('departamento_id')
                ->nullable()
                ->after('nombre')
                ->constrained('departamentos');

            $table->foreignId('municipio_id')
                ->nullable()
                ->after('departamento_id')
                ->constrained('municipios');
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
