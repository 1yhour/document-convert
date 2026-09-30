<?php
namespace App\Actions\Auth;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
class LogoutUser
{
    public function execute(Request $request) :bool
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return true;
    }
}