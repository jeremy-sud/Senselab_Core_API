<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PagoCuentaCobrar;

class PagoCuentaCobrarSeeder extends Seeder
{
    public function run(): void
    {
        PagoCuentaCobrar::factory()->count(20)->create();
    }
}
