<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Post;
use Illuminate\Http\Request;
class PostController extends Controller
{
    public function show($id){
        $post = Post::limit(10)->get();
        return response()->json([
            "message" => "Successfully",
            "data"    => $post,
        ]);
    }
}
