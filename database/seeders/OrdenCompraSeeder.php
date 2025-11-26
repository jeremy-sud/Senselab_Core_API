<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\OrdenCompra;

class OrdenCompraSeeder extends Seeder
{
    public function run(): void
    {
        OrdenCompra::factory()->count(20)->create();
    }
}
