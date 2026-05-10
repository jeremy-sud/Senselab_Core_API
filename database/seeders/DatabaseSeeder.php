<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * Estructura:
     *   - MasterDataSeeder: catálogos y datos de referencia (SIEMPRE en producción)
     *   - DemoDataSeeder: empresa demo y usuarios de prueba (SOLO desarrollo/staging)
     *
     * En producción usar: php artisan db:seed --class=MasterDataSeeder
     */
    public function run(): void
    {
        // Datos maestros: catálogos, permisos, roles, impuestos, zonas geográficas
        $this->call(MasterDataSeeder::class);

        // Datos demo: empresa Senselab, fundadores, admin
        // En producción, comentar o usar --class=MasterDataSeeder
        $this->call(DemoDataSeeder::class);
    }
}
