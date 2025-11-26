<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ComprobanteRecibidoElectronico;

class ComprobanteRecibidoElectronicoSeeder extends Seeder
{
    public function run(): void
    {
        ComprobanteRecibidoElectronico::factory()->count(20)->create();
    }
}
