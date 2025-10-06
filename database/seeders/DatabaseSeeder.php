<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
    $this->call(RoleSeeder::class);

    $admin = User::firstOrCreate([
        'email' => 'admin@lppm.test',
    ], [
        'name' => 'Admin LPPM',
        'password' => bcrypt('password123'),
    ]);

    $admin->assignRole('admin');
    $this->call(UserDataSeeder::class);
    }
}
