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
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade'); //Store es igual a establecimiento de la compania
            $table->foreignId('store_id')->constrained('stores')->onDelete('cascade');
            $table->string('nombre', 200);
            $table->enum('tipo_documento', ['DUI', 'NIT', 'Pasaporte']);
            $table->string('numero_documento', 20);
            $table->string('nrc', 20);
            $table->string('razon_social', 200);
            $table->string('actividad_economica', 200);
            $table->text('direccion_fiscal');
            $table->string('email', 100);
            $table->string('telefono', 8);
            $table->enum('tipo_cliente', ['Natural', 'Juridico']);
            $table->text('comentarios')->nullable();
            $table->timestamps();
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
