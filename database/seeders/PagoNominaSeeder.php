<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PagoNomina;

class PagoNominaSeeder extends Seeder
{
    public function run(): void
    {
        PagoNomina::factory()->count(25)->create();
    }
}
