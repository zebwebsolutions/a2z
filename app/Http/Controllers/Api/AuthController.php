<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
            'device_name' => 'required|string',
        ]);

        if (!Auth::attempt($request->only('email', 'password'))) {
            return response()->json(['message' => 'Invalid credentials.'], 401);
        }

        $user = $request->user()->load('store'); // Load store relationship

        // Check if user is active and has required role/store
        if (!$user->active || !in_array($user->role, ['admin', 'salesman'])) {
            return response()->json(['message' => 'Account ' . $user->role . ' is not authorized for mobile access'], 403);
        }

        if (!$user->store_id) {
            return response()->json(['message' => 'User is not assigned to any store'], 403);
        }

        return response()->json([
            'token' => $user->createToken($request->device_name)->plainTextToken,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'store_id' => $user->store_id,
                'store' => $user->store ? [
                    'id' => $user->store->id,
                    'name' => $user->store->name,
                ] : null,
            ],
        ]);
    }
}