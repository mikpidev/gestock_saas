<?php

namespace Database\Seeders;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Seeder;

class TipoModeloSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('tipo_modelo')->insert([
            ['codigo' => 1, 'nombre' => 'Modelo Facturacion previo'],
            ['codigo' => 2, 'nombre' => 'Modelo Facturacion diferido'],
        ]);
    }
}
