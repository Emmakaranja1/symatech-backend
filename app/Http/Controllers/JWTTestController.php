<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Facades\JWTAuth;

class JWTTestController extends Controller
{
    /**
     * Test JWT protected route access.
     */
    public function testProtectedRoute()
    {
        return response()->json([
            'message' => 'JWT protected route accessed successfully',
            'user' => auth('api')->user(),
            'token_expires_at' => auth('api')->factory()->getTTL() * 60
        ]);
    }

    /**
     * Test JWT token validation.
     */
    public function testTokenValidation(Request $request)
    {
        $token = $request->bearerToken();
        
        if (!$token) {
            return response()->json([
                'message' => 'Token not provided',
                'valid' => false
            ], 401);
        }

        try {
            $payload = JWTAuth::setToken($token)->getPayload();
            
            return response()->json([
                'message' => 'Token is valid',
                'valid' => true,
                'payload' => [
                    'sub' => $payload->get('sub'),
                    'role' => $payload->get('role'),
                    'email' => $payload->get('email'),
                    'iat' => $payload->get('iat'),
                    'exp' => $payload->get('exp'),
                ]
            ]);
        } catch (\Tymon\JWTAuth\Exceptions\TokenExpiredException $e) {
            return response()->json([
                'message' => 'Token has expired',
                'valid' => false,
                'error' => 'token_expired'
            ], 401);
        } catch (\Tymon\JWTAuth\Exceptions\TokenInvalidException $e) {
            return response()->json([
                'message' => 'Token is invalid',
                'valid' => false,
                'error' => 'token_invalid'
            ], 401);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Token validation failed',
                'valid' => false,
                'error' => 'validation_error'
            ], 401);
        }
    }

    /**
     * Test JWT refresh functionality.
     */
    public function testRefresh()
    {
        try {
            $newToken = auth('api')->refresh();
            
            return response()->json([
                'message' => 'Token refreshed successfully',
                'new_token' => $newToken,
                'token_type' => 'bearer',
                'expires_in' => auth('api')->factory()->getTTL() * 60
            ]);
        } catch (\Tymon\JWTAuth\Exceptions\TokenExpiredException $e) {
            return response()->json([
                'message' => 'Cannot refresh expired token',
                'error' => 'token_expired'
            ], 401);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Token refresh failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Test dual authentication system.
     */
    public function testDualAuth()
    {
        $user = null;
        $auth_method = null;
        
        // Check JWT authentication
        if (auth('api')->check()) {
            $user = auth('api')->user();
            $auth_method = 'JWT';
        }
        // Check Sanctum authentication
        elseif (auth()->check()) {
            $user = auth()->user();
            $auth_method = 'Sanctum';
        }
        
        return response()->json([
            'message' => 'Dual authentication test',
            'authenticated' => $user !== null,
            'auth_method' => $auth_method,
            'user' => $user,
            'systems_available' => [
                'jwt' => auth('api')->check(),
                'sanctum' => auth()->check()
            ]
        ]);
    }
}
