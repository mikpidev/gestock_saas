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
        Schema::create('mh_access', function (Blueprint $table) {
            $table->foreignId('store_id')
            ->constrained()
            ->cascadeOnDelete(); 
            $table->string('api_key')->nullable();
            $table->string('password_pri')->nullable();
            $table->integer('port_firma_digital')->nullable(); //este sera el puerto donde correra el firmador electronico
            //created_at y updated_at
            $table->timestamps();
            //softdelete
            $table->softDeletes();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mh_access', function (Blueprint $table) {
            $table->dropForeign(['store_id']);
            $table->dropColumn(['store_id', 'api_key', 'password_pri', 'port_firma_digital']);
        }); 
    }
};
