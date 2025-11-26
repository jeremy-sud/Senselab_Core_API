<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Cabys;

class CabySeeder extends Seeder
{
    public function run(): void
    {
        Cabys::factory()->count(50)->create();
    }
}
