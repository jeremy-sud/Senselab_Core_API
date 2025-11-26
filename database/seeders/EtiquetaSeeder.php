<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Etiqueta;

class EtiquetaSeeder extends Seeder
{
    public function run(): void
    {
        Etiqueta::factory()->count(20)->create();
    }
}
