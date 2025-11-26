<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ConfiguracionApi;

class ConfiguracionApiSeeder extends Seeder
{
    public function run(): void
    {
        ConfiguracionApi::factory()->count(15)->create();
    }
}
