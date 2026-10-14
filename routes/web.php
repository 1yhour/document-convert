<?php

use App\Http\Controllers\Api\CloudConvertWebhookController;
use App\Services\CloudConvertService;
use Illuminate\Http\Request;
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

Route::get('/', function () {
    return view('welcome');
});

// Fallback webhook handlers in case CloudConvert webhook is configured without /api or at root
Route::post('/webhooks/cloudconvert', [CloudConvertWebhookController::class, 'handle']);

Route::post('/', function (Request $request, CloudConvertService $service, CloudConvertWebhookController $controller) {
    if ($request->hasHeader('CloudConvert-Signature')) {
        return $controller->handle($request, $service);
    }

    return response()->json(['message' => 'Method Not Allowed'], 405);
});