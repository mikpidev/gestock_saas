<?php

namespace Database\Seeders;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Seeder;

class TipoOperacionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('tipo_operacion')->insert([
            ['codigo' => 1, 'nombre' => 'Transmision normal'],
            ['codigo' => 2, 'nombre' => 'Transmision por contigencia'],
        ]);
    }
}
