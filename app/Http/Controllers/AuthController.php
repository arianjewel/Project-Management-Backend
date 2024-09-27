<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Resources\UserResource;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function signup(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'email' => 'required|email|unique:users',
            'password' => 'required|string|min:6|confirmed'
        ]);

        User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        return response()->json(['message' => 'User Register Successfully']);

    }

    public function signin(Request $request){

        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string|min:6'
        ]);

        if (!Auth::attempt($request->only('email', 'password'))) {
            return $this->error([
                'status' => 'error',
                'message' => 'Invalid login details'
            ], 401);
        }

        $user = Auth::user();

        $token = $user->createToken('API Token of ' . $user->name)->plainTextToken;
        $user = UserResource::make($user);

        return response()->json([
            'user' => $user,
            'token' => $token
        ]);
    }


    public function logout()
    {
        $token = auth()->user()->currentAccessToken();
        $token->delete();

        return response()->json(['message' => 'Logged out successfully']);
    }
}
