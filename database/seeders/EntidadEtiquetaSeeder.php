<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\EntidadEtiqueta;

class EntidadEtiquetaSeeder extends Seeder
{
    public function run(): void
    {
        EntidadEtiqueta::factory()->count(50)->create();
    }
}
