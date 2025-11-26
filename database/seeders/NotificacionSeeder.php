<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Notificacion;

class NotificacionSeeder extends Seeder
{
    public function run(): void
    {
        Notificacion::factory()->count(50)->create();
    }
}
