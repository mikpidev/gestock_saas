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
        Schema::create('customers', function (Blueprint $table) {
            $table->id();

            $table->string('nit', 14)->unique();
            $table->string('nrc', 10)->nullable();
            $table->string('nombre');
            $table->string('codActividad', 10);
            $table->string('descActividad')->nullable();
            $table->string('nombreComercial')->nullable();

            $table->string('direccion_departamento', 2);
            $table->string('direccion_municipio', 2);
            $table->string('direccion_complemento')->nullable();

            $table->string('telefono', 15)->nullable();
            $table->string('correo')->nullable();

            $table->timestamps();
            $table->softDeletes();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
