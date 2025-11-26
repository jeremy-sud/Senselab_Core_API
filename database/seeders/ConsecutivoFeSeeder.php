<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ConsecutivoFe;

class ConsecutivoFeSeeder extends Seeder
{
    public function run(): void
    {
        ConsecutivoFe::factory()->count(5)->create();
    }
}
