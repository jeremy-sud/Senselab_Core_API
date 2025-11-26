<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\RolUsuario;

class RolUsuarioSeeder extends Seeder
{
    public function run(): void
    {
        RolUsuario::factory()->count(30)->create();
    }
}
