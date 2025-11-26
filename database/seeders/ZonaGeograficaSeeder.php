<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ZonaGeografica;

class ZonaGeograficaSeeder extends Seeder
{
    public function run(): void
    {
        ZonaGeografica::factory()->count(20)->create();
    }
}
