<?php

namespace Database\Seeders;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Seeder;

class TipoDocumentoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('tipo_documento')->insert([
            ['codigo' => '01', 'nombre' => 'Factura'],
            ['codigo' => '03', 'nombre' => 'Comprobante de crédito fiscal'],
            ['codigo' => '04', 'nombre' => 'Nota de remisión'],
            ['codigo' => '05', 'nombre' => 'Nota de crédito'],
            ['codigo' => '06', 'nombre' => 'Nota de débito'],
            ['codigo' => '07', 'nombre' => 'Comprobante de retención'],
            ['codigo' => '08', 'nombre' => 'Comprobante de liquidación'],
            ['codigo' => '09', 'nombre' => 'Documento contable de liquidación'],
            ['codigo' => '11', 'nombre' => 'Factura de exportación'],
            ['codigo' => '14', 'nombre' => 'Factura de sujeto excluido'],
            ['codigo' => '15', 'nombre' => 'Comprobante de donación'],
        ]);
    }
}
