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

            ['codigo' => "36", 'nombre' => 'NIT'],
            ['codigo' => "13", 'nombre' => 'DUI'],
            ['codigo' => "37", 'nombre' => 'Otro'],
            ['codigo' => "03", 'nombre' => 'Pasaporte'],
            ['codigo' => "02", 'nombre' => 'Carnet de Residente']

        ]);
    }
}

