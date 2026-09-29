<?php
use Illuminate\Support\Facades\Route;

Route::get('/session-write', function () {
    session([
        'name' => 'Nico',
        'lesson' => 'Cookies and sessions',
    ]);

    return response()->json([
        'message' => 'Session data was stored.',
        'session_id' => session()->getId(),
        'name' => session('name'),
        'lesson' => session('lesson'),
    ]);
});

Route::get('/session-read', function () {
    return response()->json([
        'message' => 'Trying to read session data.',
        'session_id' => session()->getId(),
        'name' => session('name', 'No name found'),
        'lesson' => session('lesson', 'No lesson found'),
    ]);
});

Route::get('/', function (){
    return view('welcome');
});