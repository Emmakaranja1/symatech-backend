<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Spatie\Activitylog\Facades\Activity;

class JWTAuthController extends Controller
{
    /**
     * Register a new user.
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'user',
            'status' => 'active',
        ]);

        // Log activity
        activity()
            ->causedBy($user)
            ->performedOn($user)
            ->withProperties(['ip' => $request->ip()])
            ->log('User registered via JWT');

        $token = JWTAuth::fromUser($user);

        return response()->json([
            'message' => 'User registered successfully',
            'user' => $user,
            'token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth('api')->factory()->getTTL() * 60
        ], 201);
    }

    /**
     * Login user and return JWT token.
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $credentials = $request->only('email', 'password');

        try {
            if (!$token = JWTAuth::attempt($credentials)) {
                return response()->json([
                    'message' => 'Invalid credentials',
                    'error' => 'Unauthorized'
                ], 401);
            }
        } catch (JWTException $e) {
            return response()->json([
                'message' => 'Could not create token',
                'error' => 'Token creation failed'
            ], 500);
        }

        $user = auth('jwt')->user();

        // Log activity - check if user exists first
        if ($user) {
            activity()
                ->causedBy($user)
                ->performedOn($user)
                ->withProperties(['ip' => $request->ip()])
                ->log('User logged in via JWT');
        }

        return response()->json([
            'message' => 'Login successful',
            'user' => $user,
            'token' => $token,
            'token_type' => 'bearer',
            'expires_in' => 60 * 60 // 60 minutes TTL
        ]);
    }

    /**
     * Get the authenticated User.
     */
    public function me()
    {
        return response()->json(auth('api')->user());
    }

    /**
     * Log the user out (Invalidate the token).
     */
    public function logout()
    {
        auth('jwt')->logout();

        // Log activity
        activity()
            ->causedBy(auth('jwt')->user())
            ->withProperties(['ip' => request()->ip()])
            ->log('User logged out via JWT');

        return response()->json(['message' => 'Successfully logged out']);
    }

    /**
     * Refresh a token.
     */
    public function refresh()
    {
        try {
            $newToken = auth('jwt')->refresh();
            $user = auth('jwt')->user();

            // Log activity
            activity()
                ->causedBy($user)
                ->withProperties(['ip' => request()->ip()])
                ->log('JWT token refreshed');

            return response()->json([
                'message' => 'Token refreshed successfully',
                'user' => $user,
                'token' => $newToken,
                'token_type' => 'bearer',
                'expires_in' => auth('jwt')->factory()->getTTL() * 60
            ]);
        } catch (JWTException $e) {
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
     * Register a new admin user.
     */
    public function registerAdmin(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $admin = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'admin',
            'status' => 'active',
        ]);

        // Log activity
        activity()
            ->causedBy($admin)
            ->performedOn($admin)
            ->withProperties(['ip' => $request->ip()])
            ->log('Admin registered via JWT');

        $token = JWTAuth::fromUser($admin);

        return response()->json([
            'message' => 'Admin registered successfully',
            'user' => $admin,
            'token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth('api')->factory()->getTTL() * 60
        ], 201);
    }
}
