<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Post;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class PostController extends Controller
{
    public function index()
    {
        return Post::latest()->get();
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string', 'max:255'],
            'short_description' => ['required', 'string', 'max:500'],
            'long_description' => ['required', 'string'],
            'image_path' => ['required', 'image', 'max:2048'], // Added image rule
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 400);
        }

        $this->createImagesDirectory(); // Create the images directory if it doesn't exist

        $imagePath = $request->file('image_path')->store('public/images'); // Store the image

        $post = new Post;

        $post->title = $request->input('title');
        $post->category = $request->input('category');
        $post->short_description = $request->input('short_description');
        $post->long_description = $request->input('long_description');
        $post->image_path = $imagePath; // Set the image URL to the path returned by store

        $post->save();

        return response()->json([
            'message' => 'Post created successfully!',
            'data' => $post
        ], 201);
    }

    public function createImagesDirectory()
    {
        Storage::disk('public')->makeDirectory('images');
    }

    public function show($id)
    {
        $post = Post::findOrFail($id);

        // add the image URL to the post data array
        $post->image_url = asset($post->image_path);

        return response()->json(['data' => $post]);
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'title' => ['string', 'max:255'],
            'category' => ['string', 'max:255'],
            'short_description' => ['string', 'max:500'],
            'long_description' => ['string'],
            'image_path' => ['string', 'max:255', 'image'], // Added image rule
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 400);
        }

        $post = Post::find($id);

        if (!$post) {
            return response()->json([
                'message' => 'Post not found'
            ], 404);
        }

        if ($request->hasFile('image_path')) {
            $this->createImagesDirectory(); // Create the images directory if it doesn't exist

            $imagePath = $request->file('image_path')->store('public/images');
            $post->image_path = $imagePath;
        }

        $post->update($request->all());

        // add the image URL to the post data array
        $post->image_url = asset($post->image_path);

        return response()->json([
            'message' => 'Post updated successfully!',
            'data' => $post
        ]);
    }



    public function destroy($id)
    {
        $post = Post::find($id);

        if (!$post) {
            return response()->json([
                'message' => 'Post not found'
            ], 404);
        }

        $post->delete();

        return response()->json([
            'message' => 'Post deleted successfully!'
        ]);
    }
}
