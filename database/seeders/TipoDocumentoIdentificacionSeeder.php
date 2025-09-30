<?php

namespace Database\Seeders;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Seeder;

class TipoDocumentoIdentificacionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        DB::table('tipo_documento_identificacion')->insert([

            ['codigo' => "01", 'nombre' => 'Sucursal'],
            ['codigo' => "02", 'nombre' => 'Casa Matriz'],
            ['codigo' => "04", 'nombre' => 'Bodega'],
            ['codigo' => "07", 'nombre' => 'Patio']

        ]);
    }
}

