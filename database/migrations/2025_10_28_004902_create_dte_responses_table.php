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
        Schema::create('dte_responses', function (Blueprint $table) {
            $table->id();

            $table->foreignId('sale_id')->constrained()->onDelete('cascade');

            $table->integer('version')->nullable();
            $table->string('ambiente')->nullable();
            $table->integer('versionApp')->nullable();
            $table->string('estado')->nullable();
            $table->string('codigo_generacion')->nullable();
            $table->string('sello_recibido')->nullable();
            $table->timestamp('fh_procesamiento')->nullable();
            $table->string('clasifica_msg')->nullable();
            $table->string('codigo_msg')->nullable();
            $table->string('descripcion_msg')->nullable();
            $table->json('observaciones')->nullable();
            $table->timestamps();
            $table->softDeletes();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dte_responses');
    }
};
