<?php

namespace Database\Seeders;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Seeder;

class MunicipiosSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('municipios')->insert([
            ['codigo' => '00', 'nombre' => 'Otro (Para extranjeros)', 'departamento_id' => 1],
            ['codigo' => '13', 'nombre' => 'AHUACHAPAN NORTE', 'departamento_id' => 2],
            ['codigo' => '14', 'nombre' => 'AHUACHAPAN CENTRO', 'departamento_id' => 2],
            ['codigo' => '15', 'nombre' => 'AHUACHAPAN SUR', 'departamento_id' => 2],
            ['codigo' => '14', 'nombre' => 'SANTA ANA NORTE', 'departamento_id' => 3],
            ['codigo' => '15', 'nombre' => 'SANTA ANA CENTRO', 'departamento_id' => 3],
            ['codigo' => '16', 'nombre' => 'SANTA ANA ESTE', 'departamento_id' => 3],
            ['codigo' => '17', 'nombre' => 'SANTA ANA OESTE', 'departamento_id' => 3],
            ['codigo' => '18', 'nombre' => 'SONSONATE NORTE', 'departamento_id' => 4],
            ['codigo' => '19', 'nombre' => 'SONSONATE CENTRO', 'departamento_id' => 4],
            ['codigo' => '20', 'nombre' => 'SONSONATE ESTE', 'departamento_id' => 4],
            ['codigo' => '34', 'nombre' => 'SONSONATE OESTE', 'departamento_id' => 4],
            ['codigo' => '35', 'nombre' => 'CHALATENANGO NORTE', 'departamento_id' => 5],
            ['codigo' => '36', 'nombre' => 'CHALATENANGO CENTRO', 'departamento_id' => 5],
            ['codigo' => '23', 'nombre' => 'LA LIBERTAD NORTE', 'departamento_id' => 6],
            ['codigo' => '24', 'nombre' => 'LA LIBERTAD CENTRO', 'departamento_id' => 6],
            ['codigo' => '25', 'nombre' => 'LA LIBERTAD OESTE', 'departamento_id' => 6],
            ['codigo' => '26', 'nombre' => 'LA LIBERTAD ESTE', 'departamento_id' => 6],
            ['codigo' => '27', 'nombre' => 'LA LIBERTAD COSTA', 'departamento_id' => 6],
            ['codigo' => '28', 'nombre' => 'LA LIBERTAD SUR', 'departamento_id' => 6],
            ['codigo' => '20', 'nombre' => 'SAN SALVADOR NORTE', 'departamento_id' => 7],
            ['codigo' => '21', 'nombre' => 'SAN SALVADOR OESTE', 'departamento_id' => 7],
            ['codigo' => '22', 'nombre' => 'SAN SALVADOR ESTE', 'departamento_id' => 7],
            ['codigo' => '23', 'nombre' => 'SAN SALVADOR CENTRO', 'departamento_id' => 7],
            ['codigo' => '24', 'nombre' => 'SAN SALVADOR SUR', 'departamento_id' => 7],
            ['codigo' => '17', 'nombre' => 'CUSCATLAN NORTE', 'departamento_id' => 8],
            ['codigo' => '18', 'nombre' => 'CUSCATLAN SUR', 'departamento_id' => 8],
            ['codigo' => '23', 'nombre' => 'LA PAZ OESTE', 'departamento_id' => 9],
            ['codigo' => '24', 'nombre' => 'LA PAZ CENTRO', 'departamento_id' => 9],
            ['codigo' => '25', 'nombre' => 'LA PAZ ESTE', 'departamento_id' => 9],
            ['codigo' => '10', 'nombre' => 'CABAÑAS OESTE', 'departamento_id' => 10],
            ['codigo' => '11', 'nombre' => 'CABAÑAS ESTE', 'departamento_id' => 10],
            ['codigo' => '14', 'nombre' => 'SAN VICENTE NORTE', 'departamento_id' => 11],
            ['codigo' => '15', 'nombre' => 'SAN VICENTE SUR', 'departamento_id' => 11],
            ['codigo' => '24', 'nombre' => 'USULUTAN NORTE', 'departamento_id' => 12],
            ['codigo' => '25', 'nombre' => 'USULUTAN ESTE', 'departamento_id' => 12],
            ['codigo' => '26', 'nombre' => 'USULUTAN OESTE', 'departamento_id' => 12],
            ['codigo' => '21', 'nombre' => 'SAN MIGUEL NORTE', 'departamento_id' => 13],
            ['codigo' => '22', 'nombre' => 'SAN MIGUEL CENTRO', 'departamento_id' => 13],
            ['codigo' => '23', 'nombre' => 'SAN MIGUEL OESTE', 'departamento_id' => 13],
            ['codigo' => '27', 'nombre' => 'MORAZAN NORTE', 'departamento_id' => 14],
            ['codigo' => '28', 'nombre' => 'MORAZAN SUR', 'departamento_id' => 14],
            ['codigo' => '19', 'nombre' => 'LA UNION NORTE', 'departamento_id' => 15],
            ['codigo' => '20', 'nombre' => 'LA UNION SUR', 'departamento_id' => 15],
        ]);
    }
}
