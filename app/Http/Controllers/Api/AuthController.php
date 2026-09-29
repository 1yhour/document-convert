<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
class AuthController extends Controller
{
    public function login(LoginRequest $request)
    {
        $credentials = $request->validated();

        // 1. Check credentials and create the session automatically
        if (! Auth::attempt($credentials)) {
            return response()->json([
                'status'  => false,
                'message' => 'Invalid email or password.',
            ], 401);
        }

        //Regenerate session ID
        $request->session()->regenerate();

        // 3. Return user data - Laravel automatically sends the 'Set-Cookie' header!
        return response()->json([
            'status'  => true,
            'message' => 'Login successful!',
            'user'    => Auth::user(),
        ]);
    }

    public function logout(Request $request)
    {
        // 1. Log out the user from the web guard
        Auth::guard('web')->logout();

        // 2. Invalidate the session on the server
        $request->session()->invalidate();

        // 3. Regenerate CSRF token
        $request->session()->regenerateToken();

        return response()->json([
            'status'  => true,
            'message' => 'Logged out successfully.',
        ]);
    }
    public function register(RegisterRequest $request)
    {
        $data = $request->validated();

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);
        Auth::login($user);
        $request->session()->regenerate();
        return response()->json([
            'status'  => true,
            'message' => 'User created successfully!',
            'data'    => $user,
        ]);
    }
}
