<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PagoCuentaPagar;

class PagoCuentaPagarSeeder extends Seeder
{
    public function run(): void
    {
        PagoCuentaPagar::factory()->count(20)->create();
    }
}
