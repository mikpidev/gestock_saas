<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            TipoDocumentoSeeder::class,
            TipoDocumentoIdentificacionSeeder::class,
            TipoModeloSeeder::class,
            TipoOperacionSeeder::class,
            TipoContigenciaSeeder::class,
            TipoEstablecimientoSeeder::class,
            FormaPagoSeeder::class,
            DepartamentosSeeder::class,
            MunicipiosSeeder::class,
            CodActividadSeeder::class,
            RolesAndPermissionsSeeder::class,
            UserSeeder::class,
            ConsumidorFinalSeeder::class,
        ]);
    }
}
