<?php
namespace App\Http\Controllers;

use Illuminate\Support\Facades\Http;
use Illuminate\Http\JsonResponse; // Add this use statement
use App\Models\Post; // Import your Post model
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;

use Illuminate\Http\Request;

class FeedController extends Controller
{
    public function showFeed(): JsonResponse // Optionally, type-hint the return for clarity
    {
        // 1. Fetch blog posts from DummyJSON
        $postsResponse = Http::get('https://dummyjson.com/posts');
        $posts = $postsResponse->json()['posts'] ?? []; // Get the 'posts' array, default to empty if not found

        // 2. Fetch all users from DummyJSON to get poster names and images
        $usersResponse = Http::get('https://dummyjson.com/users');
        $users = $usersResponse->json()['users'] ?? []; // Get the 'users' array, default to empty if not found

        // Create a map of users by their ID for efficient lookup
        $usersById = [];
        foreach ($users as $user) {
            $usersById[$user['id']] = $user;
        }

        // 3. Prepare the data for the frontend
        $blogPosts = [];
        foreach ($posts as $post) {
            $poster = $usersById[$post['userId']] ?? null; // Find the user by ID

            $blogPosts[] = [
                'id' => $post['id'],
                'title' => $post['title'], // Using 'title' as the main post content title
                'body' => $post['body'],   // The actual blog post content
                'poster_name' => $poster ? $poster['firstName'] . ' ' . $poster['lastName'] : 'Unknown User',
                'poster_image' => $poster ? $poster['image'] : 'https://placehold.co/50x50/cccccc/000000?text=NA', // Placeholder image if user not found
                'reactions' => $post['reactions'],
                'tags' => $post['tags'] ?? [],
            ];
        }

        // Combine and return posts. For simplicity, let's also fetch local posts here
        // and combine them with external ones before returning.
        // This is a more comprehensive showFeed for combined data.
        // Assuming your local posts have a 'title' or you can infer it.

        // Fetch from your database (Post model)
        // Order by latest first
        $localPosts = Post::orderBy('created_at', 'desc')->get()->map(function($post) use ($usersById) {
            // Map local posts to match the structure of dummyjson posts for consistency
            // Attempt to get user info if user_id is present and a user exists in our map
            $poster = $usersById[$post->user_id] ?? null; // Try to match if user_id corresponds to a DummyJSON user
            $posterName = $poster ? $poster['firstName'] . ' ' . $poster['lastName'] : 'Current User'; // Default if no matching user
            $posterImage = $poster ? $poster['image'] : 'https://i.pravatar.cc/40?u=' . ($post->user_id ?? 'local'); // Default avatar

            return [
                'id' => $post->id, // Prefix local IDs to avoid conflict
                'title' => $post->content, // Using content as title if no specific title column
                'body' => $post->content,
                'user_id' => $post->user_id ?? null, // Can be null if not linked to auth
                'tags' => ['local', 'custom'], // Add some default tags
                'reactions' => 0, // Default reactions
                'poster_name' => $posterName,
                'poster_image' => $posterImage,
                'image_path' => $post->image_path ? Storage::url($post->image_path) : null, // Get public URL for local image
                'created_at' => $post->created_at->toISOString(), // Ensure consistent date format
            ];
        });

        // Combine external posts with local posts. Local posts will appear first.
        // Ensure that local posts are unique and do not overlap with dummyjson IDs if possible,
        // using the 'local-' prefix helps.
        $allPosts = $localPosts->concat($blogPosts)->values()->all();

        return response()->json(['blogPosts' => $allPosts]);
    }

    // New method to handle storing a new post
    public function store(Request $request)
    {
        $request->validate([
            'content' => 'required|string|max:1000',
            'image' => 'nullable|image|max:2048', // Max 2MB, image file types
        ]);

        $imagePath = null;
        if ($request->hasFile('image')) {
            // Store the image in the 'public' disk under a 'posts' folder
            // This will be stored in storage/app/public/posts
            $imagePath = $request->file('image')->store('posts', 'public');
        }

        // Get the current authenticated user's ID
        // If you don't have authentication set up yet, you can hardcode a user_id
        // For now, let's assume user_id 1 exists or set it to null if nullable in migration
        // Ensure you have auth() available or remove this if not using Laravel Auth
        $userId = auth()->id() ?? 1; // Default to 1 if no user is logged in, adjust as per your users table

        $post = Post::create([
            'user_id' => $userId,
            'content' => $request->input('content'),
            'image_path' => $imagePath,
        ]);

        // Return the newly created post data in a format suitable for the frontend
        // This should match the structure of other posts to easily prepend
        $newPostData = [
            'id' => 'local-' . $post->id,
            'title' => $post->content, // Use content as title if no specific title column
            'body' => $post->content,
            'userId' => $post->user_id,
            'tags' => ['your', 'post', 'new'], // Example tags
            'reactions' => 0,
            'poster_name' => 'Current User', // Replace with actual logged-in user name if available
            'poster_image' => 'https://i.pravatar.cc/40?u=' . ($post->user_id ?? 'local'), // Example avatar
            'image_path' => $post->image_path ? Storage::url($post->image_path) : null,
            'created_at' => $post->created_at->toISOString(),
        ];

        return response()->json([
            'message' => 'Post created successfully!',
            'post' => $newPostData
        ], 201); // 201 Created status
    }







    public function update(Request $request, $id): JsonResponse
    {
        $post = Post::findOrFail($id); // Find the post by its ID

        // Authorization Check: Ensure the logged-in user owns this post
        if ($post->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized to update this post.'], 403);
        }

        // Validate incoming request data
        $request->validate([
            'content' => 'required_without_all:image',
            'image' => 'nullable|image|max:2048', // Max 2MB, image file types
            'image_removed' => 'nullable|boolean' // Flag from frontend to indicate image removal
        ]);

        // Update content if provided
        if ($request->has('content')) {
            $post->content = $request->input('content');
            //$post->title = substr($request->input('content'), 0, 50); // Update title from new content
        }

        // Handle image updates
        if ($request->hasFile('image')) {
            // Delete old image if it exists
            if ($post->image_path) {
                Storage::disk('public')->delete($post->image_path);
            }
            $post->image_path = $request->file('image')->store('post_images', 'public');
        } elseif ($request->input('image_removed') === true) { // Check if frontend explicitly sent signal to remove image
            if ($post->image_path) {
                Storage::disk('public')->delete($post->image_path);
                $post->image_path = null;
            }
        }

        $post->save(); // Save the changes to the database

        // Return the updated post data in a format suitable for frontend refresh
        $updatedPostData = [
            'id' => $post->id,
            'title' => $post->title,
            'body' => $post->content,
            'userId' => $post->user_id,
            'tags' => ['Updated', 'local'], // Example tags for updated post
            'reactions' => $post->reactions, // Keep original reactions or update if your logic supports it
            'poster_name' => Auth::check() ? Auth::user()->name : 'Guest User',
            'poster_image' => 'https://i.pravatar.cc/40?img=' . ($post->user_id ?? 'guest'),
            'image_path' => $post->image_path ? Storage::url($post->image_path) : null,
            'user_id' => $post->user_id, // Pass user_id for frontend comparison
        ];

        return response()->json([
            'message' => 'Post updated successfully!',
            'post' => $updatedPostData
        ]);
    }

    /**
     * Remove a specific blog post from storage.
     */
    public function destroy($id): JsonResponse
    {
        $post = Post::findOrFail($id); // Find the post by its ID

        // Authorization Check: Ensure the logged-in user owns this post
        if ($post->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized to delete this post.'], 403);
        }

        // Delete associated image from storage
        if ($post->image_path) {
            Storage::disk('public')->delete($post->image_path);
        }

        $post->delete(); // Delete the post from the database

        return response()->json(['message' => 'Post deleted successfully!']);
    }

}
