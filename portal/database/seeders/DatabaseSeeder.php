<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RoleUsersSeeder::class,   // admin + sample customer
            TspUsersSeeder::class,   // 29 FSEs / ITS / Managers pulled from Monday
        ]);
    }
}
