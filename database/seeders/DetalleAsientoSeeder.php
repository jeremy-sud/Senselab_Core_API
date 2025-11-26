<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\DetalleAsiento;

class DetalleAsientoSeeder extends Seeder
{
    public function run(): void
    {
        DetalleAsiento::factory()->count(40)->create();
    }
}
