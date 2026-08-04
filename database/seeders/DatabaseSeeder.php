<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Slot;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        Slot::factory()
            ->count(30)
            ->create();
    }
}
