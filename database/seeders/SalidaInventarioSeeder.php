<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\SalidaInventario;

class SalidaInventarioSeeder extends Seeder
{
    public function run(): void
    {
        SalidaInventario::factory()->count(20)->create();
    }
}
