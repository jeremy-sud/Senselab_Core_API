<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Update admin@senselab.com to admin@scisenselab.com in usuarios
        DB::table('usuarios')
            ->where('email', 'admin@senselab.com')
            ->update([
                'email' => 'admin@scisenselab.com',
                'password_hash' => Hash::make('Senselab2024!'),
                'activo' => true,
                'eliminado' => false
            ]);

        // Also update in case admin@scisenselab.com already exists to ensure password is correct
        DB::table('usuarios')
            ->where('email', 'admin@scisenselab.com')
            ->update([
                'password_hash' => Hash::make('Senselab2024!'),
                'activo' => true,
                'eliminado' => false
            ]);

        // 2. Update admin@senselab.com to admin@scisenselab.com in empleados
        DB::table('empleados')
            ->where('email', 'admin@senselab.com')
            ->update([
                'email' => 'admin@scisenselab.com',
                'activo' => true,
                'eliminado' => false
            ]);

        // 3. Assign Super Administrador role to deadmooncr@gmail.com
        $deadmoonUser = DB::table('usuarios')
            ->where('email', 'deadmooncr@gmail.com')
            ->first();

        if ($deadmoonUser) {
            // Reset password to Senselab2024!
            DB::table('usuarios')
                ->where('id', $deadmoonUser->id)
                ->update([
                    'password_hash' => Hash::make('Senselab2024!'),
                    'activo' => true,
                    'eliminado' => false
                ]);

            // Assign Super Administrador (rol_id = 1)
            DB::table('rol_usuario')->updateOrInsert(
                [
                    'usuario_id' => $deadmoonUser->id,
                    'rol_id' => 1 // Super Administrador
                ],
                [
                    'activo' => true,
                    'eliminado' => false
                ]
            );

            // Assign Administrador (rol_id = 2)
            DB::table('rol_usuario')->updateOrInsert(
                [
                    'usuario_id' => $deadmoonUser->id,
                    'rol_id' => 2 // Administrador
                ],
                [
                    'activo' => true,
                    'eliminado' => false
                ]
            );
        }

        // 4. Update jeremy@senselab.com to deadmooncr@gmail.com in empleados
        DB::table('empleados')
            ->where('email', 'jeremy@senselab.com')
            ->update([
                'email' => 'deadmooncr@gmail.com',
                'activo' => true,
                'eliminado' => false
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No rollback required for data normalization
    }
};
