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
        $this->command->info('🌱 Iniciando seeders de datos maestros...');

        // Seeders de datos maestros del sistema
        $this->call([
            RegimenesTributariosSeeder::class,
            FormasPagoSeeder::class,
            TiposCuentasSeeder::class,
            UnidadesMedidaSeeder::class,
            PermisosSeeder::class,
            RolesSeeder::class,
        ]);

        $this->command->info('✅ Seeders de datos maestros ejecutados correctamente.');

        $this->command->info('🌱 Iniciando seeders de datos demo...');

        // Seeders de datos demo (empresa, usuario admin)
        $this->call([
            CargosSeeder::class,
            EmpresaDemoSeeder::class,
            UsuarioAdminSeeder::class,
        ]);

        $this->command->info('✅ Seeders de datos demo ejecutados correctamente.');
    }
}
