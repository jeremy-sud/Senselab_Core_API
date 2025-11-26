<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Log;

class LogSeeder extends Seeder
{
    public function run(): void
    {
        Log::factory()->count(100)->create();
    }
}
