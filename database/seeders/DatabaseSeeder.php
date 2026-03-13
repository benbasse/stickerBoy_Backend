<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Testing\Fluent\Concerns\Has;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        // User::updateOrCreate(
        //     ['email' => 'stickerboy@gmail.com'],
        //     [
        //         'name' => 'Sticker',
        //         'lastname' => 'Boy',
        //         'phone' => '+221786536567',
        //         'role' => 'admin',
        //         'password' => Hash::make('StickerBoy123@'),
        //     ]
        // );
        User::updateOrCreate(
            ['email' => 'benbasse@gmail.com'],
            [
                'name' => 'Ben',
                'lastname' => 'Basse',
                'phone' => '+221772889670',
                'role' => 'admin',
                'password' => Hash::make('BenBasse123@'),
            ]
        );
    }
}
