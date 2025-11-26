<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\FeOAuthToken;

class FeOAuthTokenSeeder extends Seeder
{
    public function run(): void
    {
        FeOAuthToken::factory()->count(5)->create();
    }
}
