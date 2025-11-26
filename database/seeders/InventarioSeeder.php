<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\InventarioProducto;

class InventarioSeeder extends Seeder
{
    public function run(): void
    {
        InventarioProducto::factory()->count(40)->create();
    }
}
