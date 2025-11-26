<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\SesionUsuario;

class SesionUsuarioSeeder extends Seeder
{
    public function run(): void
    {
        SesionUsuario::factory()->count(40)->create();
    }
}
