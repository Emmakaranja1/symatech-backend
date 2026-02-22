<?php

namespace App\Http\Middleware;

use Closure;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Tymon\JWTAuth\Exceptions\JWTException;
use Illuminate\Http\Request;

class JWTRefreshMiddleware
{
    /**
     * Handle an incoming request and refresh token if needed.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        try {
            // Check if token exists in header
            $token = $request->bearerToken();
            
            if (!$token) {
                return response()->json([
                    'success' => false,
                    'message' => 'Token not provided',
                    'error_code' => 'TOKEN_MISSING'
                ], 401);
            }

            // Authenticate the token
            $user = JWTAuth::parseToken()->authenticate();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found',
                    'error_code' => 'USER_NOT_FOUND'
                ], 401);
            }

            // Check if user is active
            if (isset($user->status) && $user->status !== true) {
                return response()->json([
                    'success' => false,
                    'message' => 'Account is deactivated',
                    'error_code' => 'ACCOUNT_DEACTIVATED'
                ], 403);
            }

            // Check if token is close to expiry (within 5 minutes)
            $payload = JWTAuth::parseToken()->getPayload();
            $expiration = $payload->get('exp');
            $currentTime = time();
            $timeUntilExpiry = $expiration - $currentTime;

            // If token expires within 5 minutes, refresh it
            if ($timeUntilExpiry < 300) { // 5 minutes
                $newToken = JWTAuth::refresh();
                
                // Add new token to response headers
                $response = $next($request);
                $response->headers->set('New-Token', $newToken);
                $response->headers->set('Token-Refreshed', 'true');
                
                return $response;
            }

        } catch (TokenExpiredException $e) {
            // Try to refresh the expired token
            try {
                $newToken = JWTAuth::refresh();
                
                $response = response()->json([
                    'success' => false,
                    'message' => 'Token was expired but has been refreshed',
                    'error_code' => 'TOKEN_REFRESHED',
                    'new_token' => $newToken
                ], 401);
                
                $response->headers->set('New-Token', $newToken);
                $response->headers->set('Token-Refreshed', 'true');
                
                return $response;
                
            } catch (JWTException $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'Token expired and could not be refreshed',
                    'error_code' => 'TOKEN_REFRESH_FAILED'
                ], 401);
            }
            
        } catch (TokenInvalidException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid token',
                'error_code' => 'TOKEN_INVALID'
            ], 401);
            
        } catch (JWTException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Token parsing error',
                'error_code' => 'TOKEN_PARSE_ERROR'
            ], 401);
        }

        return $next($request);
    }
}
