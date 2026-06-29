<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Seeds one admin + one sample customer so we can log in as either role
 * immediately after `php artisan migrate:fresh --seed`.
 *
 * IMPORTANT: change every password before going to production.
 *
 * TSP / ITS / Manager accounts are NOT seeded here — they are pulled from
 * Monday.com in TspUsersSeeder (29 users).
 */
class RoleUsersSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name'              => 'Portal Admin',
                'password'          => Hash::make('password'),
                'role'              => 'admin',
                'status'            => 'active',
                'email_verified_at' => now(),
            ]
        );

        // Superadmin — sits above the regular admin. Lands on the
        // "Customer invites" page after login so they can issue
        // registration links straight from the UI.
        User::updateOrCreate(
            ['email' => 'superadmin@portal.local'],
            [
                'name'              => 'Super Admin',
                'password'          => Hash::make('superadmin123'),
                'role'              => 'superadmin',
                'status'            => 'active',
                'email_verified_at' => now(),
            ]
        );

        User::updateOrCreate(
            ['email' => 'customer@example.com'],
            [
                'name'              => 'Pedro Reyes',
                'password'          => Hash::make('password'),
                'role'              => 'customer',
                'status'            => 'active',
                'branch'            => 'St. Luke\'s BGC',
                'account_name'      => 'St. Luke\'s Medical Center',
                'brand'             => 'Mindray',
                'model'             => 'BC-6800',
                'email_verified_at' => now(),
            ]
        );
    }
}
