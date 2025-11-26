<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\UrlShortener;

class UrlShortenerSeeder extends Seeder
{
    public function run(): void
    {
        UrlShortener::factory()->count(30)->create();
    }
}
