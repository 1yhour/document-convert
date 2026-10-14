<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DocumentsController;
use App\Http\Controllers\Api\ProcessingJobsController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\V1\PostController;

// Public Auth routes
Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/logout', [AuthController::class, 'logout']);
});

// Protected routes (Sanctum SPA authentication)
Route::middleware(['auth:sanctum'])->group(function () {
    // 1. Authenticated user endpoint with roles and permissions exposed to frontend
    Route::get('/user', function (Request $request) {
        $user = $request->user();
        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'roles' => $user->getRoleNames(),
            'permissions' => $user->getAllPermissions()->pluck('name'),
        ]);
    });

    // 2. User dashboard endpoint
    Route::get('/dashboard', function (Request $request) {
        return response()->json([
            'status' => true,
            'message' => 'User dashboard data',
            'data' => [
                'total_conversions' => \App\Models\ProcessingJobs::where('user_id', $request->user()->id)->count(),
                'total_documents' => \App\Models\Documents::where('user_id', $request->user()->id)->count(),
            ],
        ]);
    })->middleware('permission:view own dashboard');

    // 3. Admin users list endpoint
    Route::prefix('admin')->name('admin.')->group(function () {
        Route::get('/users', [UserController::class, 'index'])
            ->middleware('permission:view all users')
            ->name('users.index');
    });

    // 4. Conversion / Processing Jobs endpoints
    Route::prefix('processing-jobs')->name('processing-jobs.')->controller(ProcessingJobsController::class)->group(function () {
        Route::get('/', 'index')->middleware('permission:convert documents');
        Route::post('/', 'store')->middleware('permission:convert documents');
        Route::get('/{processingJob}', 'show')->middleware('permission:convert documents');
        Route::get('/{processingJob}/download', 'download')->middleware('permission:download converted files');
        Route::delete('/{processingJob}', 'destroy')->middleware('permission:convert documents');
    });

    // 5. Documents endpoints
    Route::prefix('documents')->name('documents.')->controller(DocumentsController::class)->group(function () {
        Route::get('/', 'index')->middleware('permission:convert documents');
        Route::post('/', 'store')->middleware('permission:convert documents');
        Route::get('/{document}', 'show')->middleware('permission:convert documents');
        Route::get('/{document}/download', 'download')->middleware('permission:download converted files');
        Route::delete('/{document}', 'destroy')->middleware('permission:convert documents');
    });
});

Route::get('v1/posts/create', [PostController::class, 'create']);
Route::get('v1/posts/{posts:id}', [PostController::class, 'show'])->whereNumber('post');
