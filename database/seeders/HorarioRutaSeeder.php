<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\HorarioRuta;

class HorarioRutaSeeder extends Seeder
{
    public function run(): void
    {
        HorarioRuta::factory()->count(30)->create();
    }
}
