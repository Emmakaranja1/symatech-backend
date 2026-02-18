<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    // Admin: Get all users
    public function index()
    {
        return response()->json(User::all());
    }

    // Admin: Activate user
    public function activate(Request $request)
    {
        $request->validate([
            'user_id' => 'required|integer|exists:users,id',
        ]);

        $user = User::find($request->user_id);
        $user->status = true; 
        $user->save();

        return response()->json([
            'message' => 'User activated successfully',
        ], 200);
    }

    // Admin: Deactivate user
    public function deactivate(Request $request)
    {
        $request->validate([
            'user_id' => 'required|integer|exists:users,id',
        ]);

        $user = User::find($request->user_id);
        $user->status = false; 
        $user->save();

        return response()->json([
            'message' => 'User deactivated successfully',
        ], 200);
    }
}
