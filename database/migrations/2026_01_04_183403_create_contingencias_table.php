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
        Schema::create('contingencias', function (Blueprint $table) {
            $table->id();

            $table->foreignId('store_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('tipo_contingencia_id')
                ->constrained('tipo_contingencia');

            $table->string('codigo_generacion')->unique();

            $table->timestamp('fecha_hora_inicio');
            $table->timestamp('fecha_hora_fin')->nullable();

            $table->enum('estado', ['ABIERTA', 'CERRADA'])->default('ABIERTA');

            $table->string('motivo_contingencia')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contingencias');
    }
};
