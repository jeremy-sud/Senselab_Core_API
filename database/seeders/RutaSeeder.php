<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Ruta;

class RutaSeeder extends Seeder
{
    public function run(): void
    {
        Ruta::factory()->count(15)->create();
    }
}
