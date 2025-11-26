<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\DeduccionLegal;

class DeduccionLegalSeeder extends Seeder
{
    public function run(): void
    {
        DeduccionLegal::factory()->count(10)->create();
    }
}
