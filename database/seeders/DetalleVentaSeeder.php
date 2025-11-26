<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\DetalleVenta;

class DetalleVentaSeeder extends Seeder
{
    public function run(): void
    {
        DetalleVenta::factory()->count(60)->create();
    }
}
