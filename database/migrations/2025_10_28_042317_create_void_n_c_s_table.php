<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('void_nc', function (Blueprint $table) {
            $table->id();
            $table->foreignId('credit_note_id')->constrained()->onDelete('cascade');

            // Campos de anulación
            $table->string('codigo_generacion')->nullable();
            $table->dateTime('void_date')->nullable();
            $table->text('desc')->nullable();

            // Respuesta de Hacienda
            $table->string('estado')->nullable();
            $table->string('sello_recibido')->nullable();
            $table->json('response_json')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('void_nc');
    }
};
