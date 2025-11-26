<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Archivo;

class ArchivoSeeder extends Seeder
{
    public function run(): void
    {
        Archivo::factory()->count(30)->create();
    }
}
