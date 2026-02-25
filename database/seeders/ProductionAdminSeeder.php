<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class ProductionAdminSeeder extends Seeder
{
    public function run()
    {
        // Create production admin account
        User::updateOrCreate(
            ['email' => 'admin@symatech.com'],
            [
                'name' => 'Production Admin',
                'email' => 'admin@symatech.com',
                'password' => Hash::make('Admin@123'),
                'role' => 'admin',
                'status' => true
            ]
        );
        
        // Create backup admin account
        User::updateOrCreate(
            ['email' => 'backup@symatech.com'],
            [
                'name' => 'Backup Admin',
                'email' => 'backup@symatech.com',
                'password' => Hash::make('Admin@123'),
                'role' => 'admin',
                'status' => true
            ]
        );
        
        $this->command->info('Production admin accounts created successfully!');
    }
}
