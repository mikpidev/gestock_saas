<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('debit_notes', function (Blueprint $table) {
            $table->foreignId('contingencia_id')
                ->nullable()
                ->after('dte_status')
                ->constrained('contingencias')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('debit_notes', function (Blueprint $table) {
            $table->dropForeign(['contingencia_id']);
            $table->dropColumn('contingencia_id');
        });
    }
};
