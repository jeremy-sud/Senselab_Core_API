<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\TipoCliente;

class TipoClienteSeeder extends Seeder
{
    public function run(): void
    {
        TipoCliente::factory()->count(8)->create();
    }
}
