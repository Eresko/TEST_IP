<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Notification;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {

        $users = User::factory()->count(50)->create();


        Notification::factory()
            ->count(1000)
            ->recycle($users)
            ->create();

    }
}
