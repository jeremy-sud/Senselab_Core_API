<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\DetalleEntradaInventario;

class DetalleEntradaInventarioSeeder extends Seeder
{
    public function run(): void
    {
        DetalleEntradaInventario::factory()->count(40)->create();
    }
}
