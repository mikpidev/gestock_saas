<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sale_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_type_id')->constrained()->onDelete('cascade');

            $table->integer('quantity');
            $table->decimal('unit_price', 15, 2);
            $table->decimal('subtotal', 15, 2);

            // Campos nuevos para DTE
            $table->integer('tipo_item')->default(1);
            $table->string('codigo')->nullable();
            $table->string('cod_tributo')->nullable();
            $table->decimal('venta_no_suj', 15, 2)->default(0);
            $table->decimal('venta_exenta', 15, 2)->default(0);
            $table->decimal('venta_gravada', 15, 2)->default(0);
            $table->decimal('iva_item', 15, 2)->default(0);
            $table->decimal('psv', 15, 2)->default(0);
            $table->decimal('no_gravado', 15, 2)->default(0);
            $table->decimal('monto_descuento', 15, 2)->default(0);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_details');
    }
};
