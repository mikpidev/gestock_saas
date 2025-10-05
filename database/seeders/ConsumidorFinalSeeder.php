<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Customer;
use App\Models\Store;

class ConsumidorFinalSeeder extends Seeder
{
    public function run(): void
    {
        // Obtener todas las tiendas
        $stores = Store::all();

        foreach ($stores as $store) {
            // Crea el cliente genérico solo si no existe para esta tienda
            Customer::firstOrCreate(
                [
                    'nombre' => 'Consumidor Final',
                    'store_id' => $store->id, // Asociar al store correcto
                ],
                [
                    'tipoDocumento' => null,
                    'numDocumento' => null,
                    'nrc' => null,
                    'nombreComercial' => null,
                    'direccion_departamento' => '06',
                    'direccion_municipio' => '20',
                    'codActividad' => null,
                    'descActividad' => null,
                    'direccion_complemento' => null,
                    'telefono' => null,
                    'correo' => null,
                ]
            );
        }
    }
}
