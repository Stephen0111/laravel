<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\File;
use App\Http\Controllers\FeedController;
use App\Http\Controllers\AuthController;


// A minimal test POST route

Route::post('/test-post-final', function () {

    return response()->json(['message' => 'Final test POST route is working!']);

});


// Your GET blog-posts route (we know this works)




// Standard user route (often comes with Laravel)

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {

    return $request->user();

});
