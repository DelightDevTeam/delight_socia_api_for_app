<?php

namespace App\Http\Controllers\Api\V1\User;

use Log;
use App\Models\Admin\Blog;
use App\Models\Admin\Banner;
use App\Models\Admin\Like;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\Admin\Comment;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Http\Resources\User\LikeResponse;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\User\BlogHomeResource;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;




class HomeApiController extends Controller
{
    public function index()
    {
    $blogs = Blog::withCount(['likes', 'comments'])
        ->with(['medias', 'users', 'likes', 'comments', 'comments.users', 'likes.user'])
        ->latest()
        ->paginate(9);

    foreach ($blogs as $blog) {
        $blog->desc = Str::limit($blog->description, 250, '...');
    }
    return response()->json([
        'data' => $blogs
    ]);
    }

    public function home()
    {
        $blogs = Blog::withCount(['likes', 'comments'])
        ->with(['medias', 'users', 'likes', 'comments', 'comments.users', 'likes.user'])
        ->latest()
        ->paginate(9);

        foreach ($blogs as $blog) {
            $blog->desc = Str::limit($blog->description, 250, '...');
            $blog->posted_date = $blog->created_at->format('M j, Y');
            $blog->last_time = $this->calculateTimeDuration($blog->created_at);
        }
        return response()->json($blogs);
    }
    private function calculateTimeDuration($created_at)
    {
        $now = Carbon::now();
        $postedTime = Carbon::createFromFormat('Y-m-d H:i:s', $created_at);

        // Calculate time difference
        $diff = $postedTime->diffForHumans($now);

        // Replace "before" with "ago"
        $diff = str_replace('before', 'ago', $diff);

        return $diff;
    }


    public function banners(){
        $banners = Banner::latest()->first();
        // $banners = Banner::all();
        return response()->json($banners);
    }


    public function search(Request $request): JsonResponse
    {
        $search = $request->search;

        if (empty($search)) {
            return response()->json(['error' => 'No search term provided'], 400);
        }

        $blogs = Blog::where('title', 'like', '%' . $search . '%')
                     ->latest()
                     ->paginate(9);

        return response()->json(['blogs' => $blogs, 'search' => $search], 200);
    }

    public function searchBlog($search){
        $blogs = Blog::withCount(['likes', 'comments'])
        ->with(['users', 'likes', 'comments', 'comments.users', 'likes.user'])->where('description', 'like', '%' . $search . '%')
                ->latest()
                ->paginate(9);
        return response()->json([
            'blogs' => $blogs,
            'search' => $search
            ], 200);
    }

public function like(Request $request, $id)
{
    // Check if the user is authenticated
    if (auth()->check()) {
        $user_id = auth()->id(); // Get the authenticated user's ID

        // Find the blog
        $blog = Blog::find($id);

        if (!$blog) {
            return response()->json(['error' => 'Blog Not Found!'], 404);
        }

        // Validate the request data
        $validator = Validator::make($request->all(), [
            'like' => 'required|boolean', // Assuming you send 'like' as a boolean value
        ]);
        // \Log::info('User ID: ' . $user_id);
       // \Log::info('Blog ID: ' . $id);
       // \Log::info('Request Data: ' . json_encode($request->all()));
        //\Log::info('Blog: ' . json_encode($blog));
        if ($validator->fails()) {
             //\Log::info('Validation Failed'); // Log the error
            return response()->json(['error' => $validator->errors()], 400);
        }

        // Check if the user has already liked the blog
        $like = Like::where('user_id', $user_id)->where('blog_id', $id)->first();

        if ($like) {
            // User has already liked the blog, so delete the like
            $like->delete();
            return response()->json(['message' => 'Unliked'], 200);
        } else {
            // User hasn't liked the blog, so create a new like
            Like::create([
                'user_id' => $user_id,
                'blog_id' => $id,
                'like' => $request->input('like'),
            ]);

            return response()->json(['message' => 'Liked'], 200);
        }
    } else {
        // User is not authenticated, return an unauthorized response
        //\Log::info('Unauthorized'); // Log the error
        // User is not authenticated, return an unauthorized response
        return response()->json(['error' => 'Unauthorized'], 401);
    }
}
    public function addComment(Request $request, $id)
    {
        $blog = Blog::find($id);

        if (!$blog) {
            return response()->json(['error' => 'Blog Not Found!'], 404);
        }

        $request->validate([
            'comment' => 'required'
        ]);

        Comment::create([
            'blog_id' => $id,
            'user_id' => Auth::user()->id,
            'comment' => $request->comment
        ]);

        return response()->json(['success' => 'Comment Created.'], 201);
    }

//     public function editComment(Request $request, $id): JsonResponse
// {
//     $validator = Validator::make($request->all(), [
//         'comment' => 'required|string'
//     ]);

//     if ($validator->fails()) {
//         return response()->json(['error' => $validator->errors()], 422);
//     }

//     $comment = Comment::find($id);

//     if (!$comment) {
//         return response()->json(['error' => 'Comment not found'], 404);
//     }

//     $comment->update([
//         'comment' => $request->comment,
//     ]);

//     return response()->json(['success' => 'Comment Updated.'], 200);
// }

    public function updateComment(Request $request, $id): JsonResponse
    {
        try {
            $comment = Comment::find($id);
    
            if (!$comment) {
                return response()->json(['error'=> 'Comment Not Found!'], 404);
            }
    
            $request->validate([
                'comment' => 'required'
                // You can add more validation rules here as needed
            ]);
    
            $comment->update([
                'comment' => $request->comment,
            ]);
    
            return response()->json(['success' => 'Comment Updated'], 200);
            
        } catch (\Exception $e) {
            // Log the exception or handle it accordingly
            // For logging:
            // Log::error('Error updating comment: ' . $e->getMessage());
            return response()->json(['error' => 'Something went wrong. Please try again.'], 500);
        }
    }


    public function deleteComment(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|integer'
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 422);
        }

        $comment = Comment::find($request->id);

        if (!$comment) {
            return response()->json(['error' => 'Comment not found'], 404);
        }

        Comment::destroy($request->id);

        return response()->json(['success' => 'Comment Deleted'], 200);
    }
    public function blogDetail($id): JsonResponse
{
    try {
        $blog = Blog::with(['likes', 'comments', 'users', 'comments.users', 'likes.user'])
            ->withCount(['likes', 'comments'])
            ->findOrFail($id);

        $comments = Comment::with('users')
            ->where('blog_id', $id)
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'blog' => $blog
        ], 200);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Blog not found'
        ], 404);
    }
}

}
