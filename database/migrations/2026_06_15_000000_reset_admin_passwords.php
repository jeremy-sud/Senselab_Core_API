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
        // Reset password for admin@scisenselab.com to Senselab2024!
        DB::table('usuarios')
            ->where('email', 'admin@scisenselab.com')
            ->update([
                'password_hash' => Hash::make('Senselab2024!'),
                'activo' => true,
                'eliminado' => false
            ]);

        // Reset password for jeremy@scisenselab.com to Senselab2024!
        DB::table('usuarios')
            ->where('email', 'jeremy@scisenselab.com')
            ->update([
                'password_hash' => Hash::make('Senselab2024!'),
                'activo' => true,
                'eliminado' => false
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No operation needed for rollback as it's a password correction
    }
};
