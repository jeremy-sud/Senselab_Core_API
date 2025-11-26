<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\TiqueteDetalle;

class TiqueteDetalleSeeder extends Seeder
{
    public function run(): void
    {
        TiqueteDetalle::factory()->count(50)->create();
    }
}
