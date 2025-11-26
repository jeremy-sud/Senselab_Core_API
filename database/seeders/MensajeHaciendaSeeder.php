<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\MensajeHacienda;

class MensajeHaciendaSeeder extends Seeder
{
    public function run(): void
    {
        MensajeHacienda::factory()->count(15)->create();
    }
}
