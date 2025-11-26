<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\TipoCambioHistorial;

class TipoCambioHistorialSeeder extends Seeder
{
    public function run(): void
    {
        TipoCambioHistorial::factory()->count(30)->create();
    }
}
