<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Inventario;

class InventarioSeeder extends Seeder
{
    public function run(): void
    {
        Inventario::factory()->count(40)->create();
    }
}
