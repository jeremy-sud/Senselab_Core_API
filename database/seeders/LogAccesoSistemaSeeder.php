<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\LogAccesoSistema;

class LogAccesoSistemaSeeder extends Seeder
{
    public function run(): void
    {
        LogAccesoSistema::factory()->count(100)->create();
    }
}
