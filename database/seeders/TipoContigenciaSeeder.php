<?php

namespace Database\Seeders;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Seeder;

class TipoContigenciaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //tipo_contingencia
        DB::table('tipo_contingencia')->insert([
            ['codigo' => '1', 'nombre' => 'No disponibilidad de sistema del MH'],
            ['codigo' => '2', 'nombre' => 'No disponibilidad de sistema del emisor'],
            ['codigo' => '3', 'nombre' => 'Falla en el suministro de servicio de Internet del Emisor'],
            ['codigo' => '4', 'nombre' => 'Falla en el suministro de servicio de energía eléctrica del emisor que impida la transmisión de los DTE'],
            ['codigo' => '5', 'nombre' => 'Otro (deberá digitar un máximo de 500 caracteres explicando el motivo)'],
        ]);
    }
}
