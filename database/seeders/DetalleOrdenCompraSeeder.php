<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\DetalleOrdenCompra;

class DetalleOrdenCompraSeeder extends Seeder
{
    public function run(): void
    {
        DetalleOrdenCompra::factory()->count(40)->create();
    }
}
