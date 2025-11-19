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
        // Seeders de datos base del sistema
        $this->call([
            RegimenTributarioSeeder::class,
            CargoSeeder::class,
            TipoCuentaSeeder::class,
            RolSeeder::class,
            FormaPagoSeeder::class,
            UnidadMedidaSeeder::class,
            TipoImpuestoSeeder::class,
        ]);

        $this->command->info('Seeders de datos base ejecutados correctamente.');
    }
}
