<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Venta;

class VentaSeeder extends Seeder
{
    public function run(): void
    {
        Venta::factory()->count(30)->create();
    }
}
