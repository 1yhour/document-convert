<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DocumentsController;
use App\Http\Controllers\Api\ProcessingJobsController;
use App\Http\Controllers\Api\V1\PostController;
Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
Route::prefix('auth')->group(function (){
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/logout', [AuthController::class, 'logout']);
});
Route::get('/test', [DocumentsController::class, 'index'])->middleware('auth:sanctum');
// Route::get('/users' , [DocumentsController::class, 'index'])->name('usesrs.index');
// Route::post('/users', [DocumentsController::class, 'store'])->name('users.store');
// Route::put('/users/{id}', [DocumentsController::class, 'update'])->name('user.update');
// Route::delete('/users/{id}', [DocumentsController::class, 'destroy'])->name('users.destroy');
// Route::get('/users/{id}', [DocumentsController::class, 'show'])->name('users.show');
Route::prefix('admin')->name('admin.')->controller(DocumentsController::class)->middleware('auth:sanctum')->group(function (){
    Route::get('/users', 'index')->name('usesrs.index');
    Route::post('/users', 'store')->name('users.store');
    Route::put('/users/{id}', 'update')->name('users.update');
    Route::delete('/users/{id}', 'delete')->name('users.delete');
});
Route::apiResource('processing-jobs', ProcessingJobsController::class)->only(['index', 'delete', 'store']);
Route::get('v1/posts/create', [PostController::class, 'create']);
Route::get('v1/posts/{posts:id}', [PostController::class, 'show'])->whereNumber('post');

