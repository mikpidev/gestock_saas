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
        Schema::create('debit_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->date('debit_note_date');
            $table->foreignId('customers_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('sale_id')->constrained()->onDelete('cascade'); // relación con sale

            $table->date('sale_date');
            $table->decimal('total_amount', 15, 2);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('net_amount', 15, 2);

            // Campos nuevos para DTE
            $table->string('tipo_moneda', 3)->default('USD');
            $table->string('numero_control')->nullable();
            $table->string('codigo_generacion')->nullable();
            $table->integer('tipo_operacion')->default(1);
            $table->integer('tipo_contingencia')->nullable();
            $table->string('motivo_contingencia')->nullable();
            $table->integer('condicion_operacion')->default(1);

            //Documento Relacionado
            $table->string('documento_relacionado')->nullable();

            // Totales desglosados
            $table->decimal('total_no_gravado', 15, 2)->default(0);
            $table->decimal('total_exenta', 15, 2)->default(0);
            $table->decimal('total_gravada', 15, 2)->default(0);
            $table->decimal('total_iva', 15, 2)->default(0);            
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('debit_notes');
    }
};
