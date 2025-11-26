<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ModeloBus;

class ModeloBusSeeder extends Seeder
{
    public function run(): void
    {
        ModeloBus::factory()->count(10)->create();
    }
}
