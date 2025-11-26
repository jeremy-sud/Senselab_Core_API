<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\MovimientoCajaChica;

class MovimientoCajaChicaSeeder extends Seeder
{
    public function run(): void
    {
        MovimientoCajaChica::factory()->count(30)->create();
    }
}
