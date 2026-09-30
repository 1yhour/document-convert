<?php
namespace App\Actions\Auth;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class RegisterUser
{
    public function execute(array $credentials): User
    {
        $user = User::create([
            'name' => $credentials['name'],
            'email' => $credentials['email'],
            'password' => Hash::make($credentials['password']),
        ]);
        return $user;
    }
}