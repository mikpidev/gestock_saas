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
        // Llamar primero al seeder de roles y permisos
        $this->call(\Database\Seeders\RolesAndPermissionsSeeder::class);

        // Llamar al seeder de usuarios iniciales
        $this->call(\Database\Seeders\UserSeeder::class);

        // (Opcional) si quieres seguir creando usuarios de prueba
        // User::factory(10)->create();

        //llamar seeder para departamentos 
        $this->call(\Database\Seeders\DepartamentosSeeder::class);

        //llamar seeder municipios
        $this->call(\Database\Seeders\MunicipiosSeeder::class);
    }
}
