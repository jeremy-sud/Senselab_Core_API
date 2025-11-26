<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\DetallePresupuesto;

class DetallePresupuestoSeeder extends Seeder
{
    public function run(): void
    {
        DetallePresupuesto::factory()->count(40)->create();
    }
}
