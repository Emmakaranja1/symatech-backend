<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     */
     protected function redirectTo($request)
    {
        // If this is an API request, return JSON instead of redirect
        if ($request->expectsJson() || $request->is('api/*')) {
            abort(response()->json(['message' => 'Unauthenticated.'], 401));
        }

        // Otherwise, default redirect (for web)
        return route('login');
    }
}
