<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@filament-pm.test'],
            [
                'name' => 'Admin User',
                'password' => bcrypt('password'),
            ]
        )->assignRole('Product Manager');
    }
}
