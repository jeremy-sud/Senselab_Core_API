<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Seeder de datos demo: empresa, usuarios y datos de prueba
 * para desarrollo y staging.
 *
 * NO ejecutar en producción.
 * Ejecutar en desarrollo: php artisan db:seed --class=DemoDataSeeder
 */
class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Ejecutando seeders de datos demo...');

        $this->call([
            EmpresaDemoSeeder::class,      // Empresa "Senselab" + sucursal
            FoundersSeeder::class,         // Usuarios fundadores con rol Super Admin
            UsuarioAdminSeeder::class,     // admin@scisenselab.com con rol Administrador
        ]);

        $this->command->info('Datos demo cargados exitosamente.');
    }
}
