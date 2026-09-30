<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use App\Actions\Auth\LoginUser;
use App\Actions\Auth\LogoutUser;
use App\Actions\Auth\RegisterUser;
use App\Http\Resources\UserResource;
class AuthController extends Controller
{
    public function login(LoginUser $loginUser, LoginRequest $request)
    {
        $user = $loginUser->execute(
            $request->validated()
        );
        $request->session()->regenerate();
        return response()->json([
            'message' => 'Login successful!',
            'user'    => new UserResource($user),
        ]);
        
    }

    public function logout(LogoutUser $logoutUser,Request $request)
    {
        $logoutUser->execute($request);
        return response()->json([
            'status'  => true,
            'message' => 'Logged out successfully.',
        ]);

    }
    public function register(RegisterUser $registerUser, RegisterRequest $request)
    {
        $user = $registerUser->execute(
            $request->validated()
        );
        Auth::login($user);
        $request->session()->regenerate();
        return response()->json([
            'message' => 'User created successfully!',
            'data'    => new UserResource($user),
        ]);
    } 
}
