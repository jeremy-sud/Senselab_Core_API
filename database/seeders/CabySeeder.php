<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Caby;

class CabySeeder extends Seeder
{
    public function run(): void
    {
        Caby::factory()->count(50)->create();
    }
}
