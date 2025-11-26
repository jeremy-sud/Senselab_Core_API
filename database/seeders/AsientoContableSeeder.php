<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\AsientoContable;

class AsientoContableSeeder extends Seeder
{
    public function run(): void
    {
        AsientoContable::factory()->count(20)->create();
    }
}
