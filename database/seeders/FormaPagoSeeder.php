<?php

namespace Database\Seeders;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Seeder;

class FormaPagoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('forma_pago')->insert([
            ['codigo' => '01', 'nombre' => 'Billetes y monedas'],
            ['codigo' => '02', 'nombre' => 'Tarjeta Débito'],
            ['codigo' => '03', 'nombre' => 'Tarjeta Crédito'],
            ['codigo' => '04', 'nombre' => 'Cheque'],
            ['codigo' => '05', 'nombre' => 'Transferencia-Depósito Bancario'],
            ['codigo' => '08', 'nombre' => 'Dinero electrónico'],
            ['codigo' => '09', 'nombre' => 'Monedero electrónico'],
            ['codigo' => '11', 'nombre' => 'Bitcoin'],
            ['codigo' => '12', 'nombre' => 'Otras Criptomonedas'],
            ['codigo' => '13', 'nombre' => 'Cuentas por pagar del receptor'],
            ['codigo' => '14', 'nombre' => 'Giro bancario'],
            ['codigo' => '99', 'nombre' => 'Otros (se debe indicar el medio de pago)']
        ]);
    }
}
