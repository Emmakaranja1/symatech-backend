<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;

class CheckRole
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string $role)
    {
        try {
            // Get the authenticated user via JWT
            $user = JWTAuth::user();
            
            // Check if user is authenticated
            if (!$user) {
                return response()->json(['message' => 'Unauthenticated'], 401);
            }

            // Check if user role matches required role
            if ($user->role !== $role) {
                return response()->json(['message' => 'Forbidden'], 403);
            }

            // Add the user to the request for downstream use
            $request->setUserResolver(function () use ($user) {
                return $user;
            });

            return $next($request);

        } catch (\Exception $e) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }
    }
}