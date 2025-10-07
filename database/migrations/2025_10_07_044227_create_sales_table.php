<?php


use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('customers_id')->nullable()->constrained()->onDelete('set null');

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
            $table->decimal('total_no_gravado', 15, 2)->default(0);
            $table->decimal('total_exenta', 15, 2)->default(0);
            $table->decimal('total_gravada', 15, 2)->default(0);
            $table->decimal('total_iva', 15, 2)->default(0);

            $table->enum('payment_status', ['paid', 'unpaid', 'partial'])->default('unpaid');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales');
    }
};
