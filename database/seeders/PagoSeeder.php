<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Pago;

class PagoSeeder extends Seeder
{
    public function run(): void
    {
        Pago::factory()->count(30)->create();
    }
}
