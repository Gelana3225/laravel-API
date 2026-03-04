<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Post;
use Illuminate\Http\Request;

class PostController extends Controller
{
    // Get all posts
    public function index()
    {
        return response()->json(Post::all());
    }

    // Get single post
    public function show($id)
    {
        return response()->json(Post::findOrFail($id));
    }

    // Store new post
    public function store(Request $request)
    {
        $post = Post::create($request->all());

        return response()->json($post, 201);
    }
}