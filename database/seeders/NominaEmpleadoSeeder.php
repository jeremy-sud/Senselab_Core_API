<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\NominaEmpleado;

class NominaEmpleadoSeeder extends Seeder
{
    public function run(): void
    {
        NominaEmpleado::factory()->count(40)->create();
    }
}
