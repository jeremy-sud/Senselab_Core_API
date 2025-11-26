<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\CuentaPorPagar;

class CuentaPorPagarSeeder extends Seeder
{
    public function run(): void
    {
        CuentaPorPagar::factory()->count(25)->create();
    }
}
