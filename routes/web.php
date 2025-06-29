
<?php

use Illuminate\Support\Facades\Route;
// Remove 'use Illuminate\Support\Facades\File;' as we're now using Blade views
use App\Http\Controllers\FeedController;
use App\Http\Controllers\AuthController;

// Existing web routes
/* Route::get('/', function () {
    // Path to your index.html within resources/views
    $path = resource_path('views/index.html');

    // Check if the file exists
    if (!File::exists($path)) {
        abort(404, 'HTML file not found.');
    }

    // Return the file content with the correct Content-Type header
    return response(File::get($path))->header('Content-Type', 'text/html');
}); */

Route::get('/', function () {
    return view('index'); // This will now process resources/views/index.blade.php
});

// IMPORTANT: This now returns a Blade view (feed.blade.php)
Route::get('/feed', function () {
    // This line is CRUCIAL for passing the logged-in user's ID to the frontend
    $currentUserId = Auth::check() ? Auth::id() : null;
    return view('feed', ['currentUserId' => $currentUserId]);
});

Route::get('/testapi', function () {
    return response()->json(['message' => 'Web route test']);
});


// Blog Posts Routes - Now consolidated in web.php
// GET route to fetch all blog posts
Route::get('api/blog-posts', [FeedController::class, 'showFeed']); // Changed from /api/blog-posts to /blog-posts

// POST route to store a new blog post
Route::post('/blog-posts', [FeedController::class, 'store']);

Route::put('/blog-posts/{id}', [FeedController::class, 'update']);
Route::patch('/blog-posts/{id}', [FeedController::class, 'update']);

// NEW: Route to delete a blog post by ID
Route::delete('/blog-posts/{id}', [FeedController::class, 'destroy']);


// Keep your Auth routes
Route::post('/login', [AuthController::class, 'login'])->name('login');
Route::post('/register', [AuthController::class, 'register'])->name('register');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');


// Remove or comment out these old API routes from web.php if they exist:
/*

Route::get('/api/test', function () {
    return response()->json(['message' => 'API is working']);
});
*/
