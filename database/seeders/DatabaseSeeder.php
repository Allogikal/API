<?php

namespace Database\Seeders;

use App\Models\Position;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        /**
         * SEED_ADMINISTRATORS
         */
         Role::factory()->create([
             'role_title' => 'Администратор'
         ]);
        Role::factory()->create([
            'role_title' => 'Официант'
        ]);
        Role::factory()->create([
            'role_title' => 'Повар'
        ]);

        /**
         * SEED_USERS
         */
        User::factory()->create([
            'login' => 'admin',
            'password' => Hash::make('admin'),
            'role_id' => 1,
            'photo_file' => null,
            'name' => 'Alex',
            'status' => 'not working'
        ]);

        User::factory()->create([
            'login' => 'waiter',
            'password' => Hash::make('waiter'),
            'role_id' => 2,
            'photo_file' => null,
            'name' => 'Bell',
            'status' => 'not working'
        ]);

        User::factory()->create([
            'login' => 'cook',
            'password' => Hash::make('cook'),
            'role_id' => 3,
            'photo_file' => null,
            'name' => 'Estrella',
            'status' => 'not working'
        ]);
        /**
         * SEED_POSITIONS
         */
        Position::factory()->create([
            'position' => 'Position 1',
            'price' => '2000'
        ]);

        Position::factory()->create([
            'position' => 'Position 2',
            'price' => '8192'
        ]);

        Position::factory()->create([
            'position' => 'Position 3',
            'price' => '3200'
        ]);

        Position::factory()->create([
            'position' => 'Position 4',
            'price' => '1220'
        ]);
    }
}
