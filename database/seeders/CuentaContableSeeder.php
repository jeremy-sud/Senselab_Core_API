<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\CuentaContable;

class CuentaContableSeeder extends Seeder
{
    public function run(): void
    {
        CuentaContable::factory()->count(30)->create();
    }
}
