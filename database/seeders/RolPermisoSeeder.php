<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\RolPermiso;

class RolPermisoSeeder extends Seeder
{
    public function run(): void
    {
        RolPermiso::factory()->count(50)->create();
    }
}
