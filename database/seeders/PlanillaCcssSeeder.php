<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PlanillaCcss;

class PlanillaCcssSeeder extends Seeder
{
    public function run(): void
    {
        PlanillaCcss::factory()->count(30)->create();
    }
}
