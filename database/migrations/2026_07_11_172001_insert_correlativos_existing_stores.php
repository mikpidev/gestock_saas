<?php

use App\Models\Store;
use App\Models\TipoDocumento;
use App\Models\CorrelativoStore;
use Illuminate\Database\Migrations\Migration;


return new class extends Migration
{
    public function up(): void
    {
        $tiposDocumento = TipoDocumento::all();

        Store::chunk(100, function ($stores) use ($tiposDocumento) {

            foreach ($stores as $store) {

                foreach ($tiposDocumento as $tipoDocumento) {

                    CorrelativoStore::firstOrCreate([
                        'store_id' => $store->id,
                        'tipo_documento_id' => $tipoDocumento->id,
                    ], [
                        'correlativo' => 0,
                    ]);
                }
            }
        });
    }

    public function down(): void
    {
        //
    }
};
