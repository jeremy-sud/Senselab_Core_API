<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\CajaChica;

class CajaChicaSeeder extends Seeder
{
    public function run(): void
    {
        CajaChica::factory()->count(10)->create();
    }
}
