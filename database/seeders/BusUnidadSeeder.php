<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\BusUnidad;

class BusUnidadSeeder extends Seeder
{
    public function run(): void
    {
        BusUnidad::factory()->count(10)->create();
    }
}
