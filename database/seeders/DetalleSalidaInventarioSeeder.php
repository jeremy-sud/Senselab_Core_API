<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\DetalleSalidaInventario;

class DetalleSalidaInventarioSeeder extends Seeder
{
    public function run(): void
    {
        DetalleSalidaInventario::factory()->count(40)->create();
    }
}
