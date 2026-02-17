<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
  User::create([
    'name' => 'Admin',
    'email' => 'admin@symatech.com',
    'password' => Hash::make('Admin@123'),
    'role' => 'admin',
    'status' => true
]);
    }
}
