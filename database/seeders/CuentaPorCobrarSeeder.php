<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\CuentaPorCobrar;

class CuentaPorCobrarSeeder extends Seeder
{
    public function run(): void
    {
        CuentaPorCobrar::factory()->count(25)->create();
    }
}
