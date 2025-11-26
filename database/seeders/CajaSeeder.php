<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Caja;

class CajaSeeder extends Seeder
{
    public function run(): void
    {
        Caja::factory()->count(8)->create();
    }
}
