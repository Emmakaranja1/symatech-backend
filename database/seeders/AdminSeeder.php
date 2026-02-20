<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run()
    {
        User::create([
            'name' => 'Super Admin',
            'email' => 'admin@yourcompany.com',
            'password' => Hash::make('secure-admin-password'),
            'role' => 'admin',
            'status' => true
        ]);
        
        $this->command->info('Super Admin created successfully!');
    }
}
