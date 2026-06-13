<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UsuarioAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Obtener empresa demo
        $empresa = DB::table('empresas')->where('nombre', 'Senselab')->first();
        
        if (!$empresa) {
            $this->command->error('✗ Error: Empresa demo no encontrada. Ejecuta EmpresaDemoSeeder primero.');
            return;
        }

        // Obtener cargo de administrador
        $cargo = DB::table('cargos')->where('nombre', 'Administrador de Sistema')->first();
        
        if (!$cargo) {
            $this->command->error('✗ Error: Cargo no encontrado. Ejecuta CargosSeeder primero.');
            return;
        }

        // Crear usuario administrador
        $adminPassword = env('ADMIN_SEEDER_PASSWORD', 'admin123');
        
        if ($adminPassword === 'admin123') {
            $this->command->warn('⚠️  ADVERTENCIA: Usando contraseña de desarrollo para admin');
            $this->command->warn('⚠️  Cambiar ADMIN_SEEDER_PASSWORD en .env para producción');
        }

        $usuario = [
            'nombre' => 'Admin',
            'apellidos' => 'Sistema',
            'cargo_id' => $cargo->id,
            'email' => 'admin@scisenselab.com',
            'password_hash' => Hash::make($adminPassword),
            'empresa_id' => $empresa->id,
            'telefono' => '+(506)0000-0000',
            'direccion' => 'San José, Costa Rica',
            'activo' => true,
            'eliminado' => false,
        ];

        $existingUser = DB::table('usuarios')->where('email', 'admin@scisenselab.com')->first();
        $creadoNuevo = false;
        
        if ($existingUser) {
            $this->command->info('✓ El usuario admin@scisenselab.com ya existe en el sistema. Omitiendo creación.');
            $usuarioId = $existingUser->id;
        } else {
            $usuarioId = DB::table('usuarios')->insertGetId($usuario);
            $creadoNuevo = true;
        }

        // Asignar rol de Administrador
        $rolAdmin = DB::table('roles')->where('nombre', 'Administrador')->first();
        
        if ($rolAdmin) {
            // Evitar duplicación en la asignación de rol
            $existingRol = DB::table('rol_usuario')
                ->where('usuario_id', $usuarioId)
                ->where('rol_id', $rolAdmin->id)
                ->first();
                
            if (!$existingRol) {
                DB::table('rol_usuario')->insert([
                    'usuario_id' => $usuarioId,
                    'rol_id' => $rolAdmin->id,
                    'activo' => true,
                    'eliminado' => false,
                ]);
            }
 
            // Asignar TODOS los permisos al rol Administrador
            $permisos = DB::table('permisos')->where('activo', true)->where('eliminado', false)->get();
            
            foreach ($permisos as $permiso) {
                DB::table('roles_permisos')->updateOrInsert(
                    ['rol_id' => $rolAdmin->id, 'permiso_id' => $permiso->id],
                    ['activo' => true]
                );
            }
 
            if ($creadoNuevo) {
                $this->command->info('✓ Usuario administrador creado exitosamente.');
                $this->command->info('   Email: admin@scisenselab.com');
                $this->command->info('   Password: ' . $adminPassword);
            }
            $this->command->info('   Rol: Administrador (con ' . count($permisos) . ' permisos) enlazado.');
        } else {
            $this->command->error('✗ Error: Rol Administrador no encontrado.');
        }
    }
}
