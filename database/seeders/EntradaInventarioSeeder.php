<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\EntradaInventario;

class EntradaInventarioSeeder extends Seeder
{
    public function run(): void
    {
        EntradaInventario::factory()->count(20)->create();
    }
}
