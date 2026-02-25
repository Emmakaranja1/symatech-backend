<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class FixAdminSeeder extends Seeder
{
    public function run()
    {
        // Update existing admin@symatech.com to admin role
        User::where('email', 'admin@symatech.com')->update([
            'role' => 'admin',
            'password' => Hash::make('Admin@123')
        ]);
        
        $this->command->info('Admin role fixed successfully!');
    }
}
