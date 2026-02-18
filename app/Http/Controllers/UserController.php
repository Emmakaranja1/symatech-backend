<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    // Admin: Get all users
   public function index() {
    return response()->json(User::all(), 200);
}


    // Admin: Activate user
    public function activate($id) {
    $user = User::find($id);
    if (!$user) return response()->json(['message'=>'User not found'], 404);

    $user->status = true;
    $user->save();

    return response()->json(['message'=>'User activated successfully', 'user'=>$user], 200);
}


    // Admin: Deactivate user
    public function deactivate($id) {
    $user = User::find($id);
    if (!$user) return response()->json(['message'=>'User not found'], 404);

    $user->status = false;
    $user->save();

    return response()->json(['message'=>'User deactivated successfully', 'user'=>$user], 200);
}

}
