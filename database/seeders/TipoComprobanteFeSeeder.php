<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\TipoComprobanteFe;

class TipoComprobanteFeSeeder extends Seeder
{
    public function run(): void
    {
        TipoComprobanteFe::factory()->count(6)->create();
    }
}
