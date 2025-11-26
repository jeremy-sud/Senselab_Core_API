<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Almacen;

class AlmacenSeeder extends Seeder
{
    public function run(): void
    {
        Almacen::factory()->count(5)->create();
    }
}
