<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Presupuesto;

class PresupuestoSeeder extends Seeder
{
    public function run(): void
    {
        Presupuesto::factory()->count(20)->create();
    }
}
