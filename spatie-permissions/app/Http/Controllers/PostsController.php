<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Post;

class PostsController extends Controller
{  
    public function index()
    {
        $posts = Post::where('published', true)->get();
        return view('posts.index', compact('posts'));
    }

}
