<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

class DatabaseResetController extends Controller
{
    public function clearAndReseed()
    {
        try {
            // Only allow from specific origins for security
            $allowedOrigins = [
                'https://symatech-assesment-frontend.vercel.app',
                'http://localhost:3000',
                'http://localhost:5173'
            ];
            
            $origin = request()->header('Origin');
            
            if (!in_array($origin, $allowedOrigins)) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }
            
            // Clear all tables
            Artisan::call('db:wipe');
            
            // Run migrations
            Artisan::call('migrate', ['--force' => true]);
            
            // Seed with fresh data including admins
            Artisan::call('db:seed', ['--force' => true]);
            
            return response()->json([
                'success' => true,
                'message' => 'Database cleared and reseeded successfully',
                'commands' => [
                    'db:wipe - completed',
                    'migrate - completed', 
                    'db:seed - completed'
                ]
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Database reset failed: ' . $e->getMessage()
            ], 500);
        }
    }
}
