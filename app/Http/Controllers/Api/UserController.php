<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    /**
     * Display a listing of all users (admin only).
     */
    public function index(Request $request): JsonResponse
    {
        $users = User::latest()->get();

        return response()->json([
            'status' => true,
            'data' => UserResource::collection($users),
        ]);
    }
}
