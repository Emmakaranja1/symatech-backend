<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Illuminate\Validation\ValidationException;
class AuthController extends Controller
{
   // Register new user
    public function register(Request $request)
    {
        $request->validate([
            'name'=>'required|string|max:255',
            'email'=>'required|string|email|unique:users',
            'password'=>'required|string|min:6|confirmed',
            'role' => 'nullable|in:user,admin',
        ]);

        $user = User::create([
            'name'=>$request->name,
            'email'=>$request->email,
            'password'=>Hash::make($request->password),
            'role' => $request->role ?? 'user', // default to user
            'status' => true // Default status to active
        ]);

        // Log the registration
        activity()
            ->causedBy($user)
            ->log('New user registered');

        return response()->json(['message'=>'User registered successfully', 'user'=>$user], 201);
    }
 
    // Login user(user or admin)
    public function login(Request $request)
    {
        $request->validate([
            'email'=>'required|string|email',
            'password'=>'required|string',
            'role' => 'nullable|in:user,admin', 
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

    


        // Check role if provided
        if ($request->has('role') && $user->role !== $request->role) {
            return response()->json([
                'message' => 'Unauthorized for this role.'
            ], 403);
        }
       
       // Check if account is active
        if (!$user->status) {
            return response()->json([
              'message' => 'Your account is deactivated.'
    ], 403);
}
          
          // 🔥 Log only after successful validation
    activity()
        ->causedBy($user)
        ->log('User logged in');


        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out']);
    }
}
