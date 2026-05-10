<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Seeder para crear los usuarios fundadores de Senselab
 *
 * Fundadores:
 * - Eduardo Ureña Solano (Fundador, CEO)
 * - Jeremy Arias Solano (Co-Fundador, CTO)
 */
class FoundersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Obtener empresa Senselab
        $empresa = DB::table('empresas')->where('nombre', 'Senselab')->first();

        if (!$empresa) {
            $this->command->error('✗ Error: Empresa Senselab no encontrada. Ejecuta EmpresaDemoSeeder primero.');
            return;
        }

        // Obtener cargos de fundadores
        $cargoFundador = DB::table('cargos')->where('nombre', 'Fundador')->first();
        $cargoCoFundador = DB::table('cargos')->where('nombre', 'Co-Fundador')->first();

        if (!$cargoFundador || !$cargoCoFundador) {
            $this->command->error('✗ Error: Cargos de Fundador/Co-Fundador no encontrados. Ejecuta CargosSeeder primero.');
            return;
        }

        // Obtener rol Super Administrador
        $rolSuperAdmin = DB::table('roles')->where('nombre', 'Super Administrador')->first();

        if (!$rolSuperAdmin) {
            $this->command->error('✗ Error: Rol Super Administrador no encontrado. Ejecuta RolesSeeder primero.');
            return;
        }

        // =============================================================
        // FUNDADOR: Eduardo Ureña Solano
        // =============================================================
        
        // Obtener contraseña de variable de entorno o usar default
        $eduardoPassword = env('FOUNDER1_PASSWORD', 'Senselab2024!');
        
        if ($eduardoPassword === 'Senselab2024!') {
            $this->command->warn('⚠️  ADVERTENCIA: Usando contraseña de desarrollo para Eduardo');
            $this->command->warn('⚠️  Cambiar FOUNDER1_PASSWORD en .env para producción');
        }
        
        $eduardo = [
            'nombre' => 'Eduardo',
            'apellidos' => 'Ureña Solano',
            'cargo_id' => $cargoFundador->id,
            'email' => 'eduardo@senselab.com',
            'password_hash' => Hash::make($eduardoPassword),
            'empresa_id' => $empresa->id,
            'telefono' => '+(506)8973-5665',
            'direccion' => 'San José, Costa Rica',
            'activo' => true,
            'eliminado' => false,
        ];

        $eduardoId = DB::table('usuarios')->insertGetId($eduardo);

        // Asignar rol Super Administrador a Eduardo
        DB::table('rol_usuario')->insert([
            'usuario_id' => $eduardoId,
            'rol_id' => $rolSuperAdmin->id,
            'activo' => true,
            'eliminado' => false,
        ]);

        // También asignar rol Administrador normal
        $rolAdmin = DB::table('roles')->where('nombre', 'Administrador')->first();
        if ($rolAdmin) {
            DB::table('rol_usuario')->insert([
                'usuario_id' => $eduardoId,
                'rol_id' => $rolAdmin->id,
                'activo' => true,
                'eliminado' => false,
            ]);
        }

        // Crear empleado asociado a Eduardo
        DB::table('empleados')->insert([
            'empresa_id' => $empresa->id,
            'nombre' => 'Eduardo',
            'primer_apellido' => 'Ureña',
            'segundo_apellido' => 'Solano',
            'tipo_documento' => 'Cédula Nacional',
            'numero_documento' => '1-1234-5678', // Ficticio
            'email' => 'eduardo@senselab.com',
            'telefono' => '+(506)8973-5665',
            'direccion' => 'San José, Costa Rica',
            'fecha_ingreso' => '2020-01-15',
            'cargo_id' => $cargoFundador->id,
            'salario' => 0.00, // Fundador - no aplica
            'activo' => true,
            'eliminado' => false,
        ]);

        $this->command->info('✓ Usuario Eduardo Ureña Solano (Fundador) creado exitosamente.');
        $this->command->info('   Email: eduardo@senselab.com');
        $this->command->info('   Password: Senselab2024!');

        // =============================================================
        // CO-FUNDADOR: Jeremy Arias Solano
        // =============================================================
        
        // Obtener contraseña de variable de entorno o usar default
        $jeremyPassword = env('FOUNDER2_PASSWORD', 'Senselab2024!');
        
        if ($jeremyPassword === 'Senselab2024!') {
            $this->command->warn('⚠️  ADVERTENCIA: Usando contraseña de desarrollo para Jeremy');
            $this->command->warn('⚠️  Cambiar FOUNDER2_PASSWORD en .env para producción');
        }
        
        $jeremy = [
            'nombre' => 'Jeremy',
            'apellidos' => 'Arias Solano',
            'cargo_id' => $cargoCoFundador->id,
            'email' => 'jeremy@senselab.com',
            'password_hash' => Hash::make($jeremyPassword),
            'empresa_id' => $empresa->id,
            'telefono' => '+506 8765-4321',
            'direccion' => 'San José, Costa Rica',
            'activo' => true,
            'eliminado' => false,
        ];

        $jeremyId = DB::table('usuarios')->insertGetId($jeremy);

        // Asignar rol Super Administrador a Jeremy
        DB::table('rol_usuario')->insert([
            'usuario_id' => $jeremyId,
            'rol_id' => $rolSuperAdmin->id,
            'activo' => true,
            'eliminado' => false,
        ]);

        // También asignar rol Administrador normal
        if ($rolAdmin) {
            DB::table('rol_usuario')->insert([
                'usuario_id' => $jeremyId,
                'rol_id' => $rolAdmin->id,
                'activo' => true,
                'eliminado' => false,
            ]);
        }

        // Crear empleado asociado a Jeremy
        DB::table('empleados')->insert([
            'empresa_id' => $empresa->id,
            'nombre' => 'Jeremy',
            'primer_apellido' => 'Arias',
            'segundo_apellido' => 'Solano',
            'tipo_documento' => 'Cédula Nacional',
            'numero_documento' => '1-8765-4321', // Ficticio
            'email' => 'jeremy@senselab.com',
            'telefono' => '+506 8765-4321',
            'direccion' => 'San José, Costa Rica',
            'fecha_ingreso' => '2020-01-15',
            'cargo_id' => $cargoCoFundador->id,
            'salario' => 0.00, // Co-Fundador - no aplica
            'activo' => true,
            'eliminado' => false,
        ]);

        $this->command->info('✓ Usuario Jeremy Arias Solano (Co-Fundador) creado exitosamente.');
        $this->command->info('   Email: jeremy@senselab.com');
        $this->command->info('   Password: Senselab2024!');

        // =============================================================
        // Asignar TODOS los permisos al rol Super Administrador
        // =============================================================
        $permisos = DB::table('permisos')
            ->where('activo', true)
            ->where('eliminado', false)
            ->get();

        foreach ($permisos as $permiso) {
            DB::table('roles_permisos')->updateOrInsert(
                ['rol_id' => $rolSuperAdmin->id, 'permiso_id' => $permiso->id],
                ['activo' => true]
            );
        }

        $this->command->info('');
        $this->command->info('🔐 Rol Super Administrador configurado con ' . count($permisos) . ' permisos totales.');
        $this->command->info('');
        $this->command->info('═══════════════════════════════════════════════════════════');
        $this->command->info('    SISTEMAS SENSELAB S.A. - Usuarios Fundadores Creados');
        $this->command->info('═══════════════════════════════════════════════════════════');
        $this->command->info('');
        $this->command->info('  👤 Eduardo Ureña Solano (Fundador)');
        $this->command->info('     ✉️  eduardo@senselab.com');
        $this->command->info('     🔑 Senselab2024!');
        $this->command->info('');
        $this->command->info('  👤 Jeremy Arias Solano (Co-Fundador)');
        $this->command->info('     ✉️  jeremy@senselab.com');
        $this->command->info('     🔑 Senselab2024!');
        $this->command->info('');
        $this->command->info('  🛡️  Ambos usuarios tienen rol: Super Administrador');
        $this->command->info('  ⚠️  Cambiar contraseñas antes de pasar a producción!');
        $this->command->info('═══════════════════════════════════════════════════════════');
    }
}

