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
        //pasword_pri es la contraseña de la clave privada del certificado digital
        Schema::table('store_tax_infos', function (Blueprint $table) {
            $table->string('password_pri')->nullable()->after('cert_firma_digital');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
        Schema::table('store_tax_infos', function (Blueprint $table) {
            $table->dropColumn('password_pri');
        });
    }
};
