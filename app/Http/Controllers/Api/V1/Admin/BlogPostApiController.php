<?php

namespace App\Http\Controllers\Api\V1\Admin;

use Log;
use App\Models\User;
use App\Models\Admin\Blog;
use App\Models\Admin\Media;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use App\Http\Requests\BlogRequest;
use Illuminate\Support\Facades\URL;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Gate;
use App\Http\Requests\StoreBlogPostRequest;
use App\Http\Requests\UpdateBlogPostRequest;
use App\Http\Resources\Admin\BlogPostResource;
use Symfony\Component\HttpFoundation\Response;
use App\Http\Controllers\Traits\MediaUploadingTrait;
use App\Services\VideoService;
use Illuminate\Support\Facades\Storage; // Import the Storage facade

class BlogPostApiController extends Controller
{
    use MediaUploadingTrait;

    public function index()
    {
        abort_if(Gate::denies('blog_post_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');
    $blogs = Blog::withCount(['likes', 'comments'])
        ->with(['medias','users', 'likes', 'comments', 'comments.users', 'likes.user'])
        ->latest()
        ->paginate(10);
        return response()->json([
            'blogs'=> $blogs
        ]);
        // return new BlogPostResource(Blog::with(['users'])->get());
    }

    public function saveToken(Request $request)
    {
        $user = Auth::user();
        $user->update([
            'device_token'=>$request->token
        ]);
        return response()->json([
            'message' => "Notification Token Save Successfully.",
            'token' => $user->token,
        ], 200);
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'description' => 'required|string',
            'medias.*' => 'nullable', // Example validation for media files
        ]);
    
        if(!$validatedData['description']){
            return response()->json([
                'message' => 'false',
                'errors' => [
                    'description'=> 'The description must not be empty.'
                ]
            ], 400);
        }
    
        try {
            // Create the blog
            $blog = Blog::create([
                'description' => $validatedData['description'],
                'user_id' => Auth::id(),
            ]);
    
            // Handle media if present in the request
                foreach ($request->file('medias') as $media) {
                    $ext = $media->getClientOriginalExtension();
                    $mediaName = uniqid('blogs_') . '.' . $ext; // Generate unique filename
                    $file_path = $media->storeAs("assets/img/blogs", $mediaName, 'upload');
                    // $type = in_array(strtolower($ext), ['jpg', 'png', 'jpeg', 'gif', 'svg']) ? 1 : (VideoService::getPlaytimeSeconds($file_path) < 300 ? 2 : 3);
                   // Retrieve video information
                    $videoInfo = VideoService::getVideoInfo($file_path);

                    if (isset($videoInfo['error'])) {
                        // Handle the error
                        return response()->json([
                            'message' => 'Error creating the blog',
                            'error' => $videoInfo['error'],
                        ], 500);
                    }
                    // Determine media type based on playtime
                    $type = in_array(strtolower($ext), ['jpg', 'png', 'jpeg', 'gif', 'svg']) ? 1 : ($videoInfo['playtimeSeconds'] < 300 ? 2 : 3);
                    Media::create([
                        'media' => $mediaName,
                        'type' => $type,
                        'blog_id' => $blog->id,
                    ]);
                }
            
            // Eager load relationships for the created blog
            $blogPost = Blog::with(['medias', 'users'])->where('id', $blog->id)->first();
    
            // Notify users about the new post
            $blogDesc = Str::limit($blog->description, 100, '...');
            $firebaseToken = User::whereNotNull('device_token')->pluck('device_token')->all();
            $SERVER_API_KEY = 'AAAAa3dnNbk:APA91bH6EBUEkFQwNc07ULndELZyQEFrouyDnAlJ0IGuMDEmcVJoUl_g9pHnuoR-tBdYecQDPUwOQygndEcZpDix2qbN9Zo9gRbI5Z_PbyZE8yx9r8avT-9wJ7HnTn1k4-yNBvhWB3Sv';
    
            $data = [
                "registration_ids" => $firebaseToken,
                "notification" => [
                    "title" => "New Post Created",
                    "body" => $blogDesc,
                    "content_available" => true,
                    "priority" => "high",
                ]
            ];
            $dataString = json_encode($data);
    
            $headers = [
                'Authorization: key=' . $SERVER_API_KEY,
                'Content-Type: application/json',
            ];
    
            $ch = curl_init();
    
            curl_setopt($ch, CURLOPT_URL, 'https://fcm.googleapis.com/fcm/send');
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $dataString);
    
            $response = curl_exec($ch);
    
            return response()->json([
                'message' => 'Blog ' . (isset($validatedData['medias']) ? 'with Media' : '') . ' Created Successfully',
                'blog' => $blogPost,
            ], 200);
        } catch (\Exception $e) {
            // Handle exceptions or errors here
            return response()->json([
                'message' => 'Error creating the blog',
                'error' => $e->getMessage(), // Send error details (not recommended for production)
            ], 500);
        }
    }
    

    public function showDetail($id)
    {
        try {
            $blog = Blog::with(['medias', 'users'])->findOrFail($id);

            return response()->json([
                'blog' => $blog
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Blog not found'
            ], 404);
        }
    }

    public function updateBlog(Request $request, $id){
        // Validate the incoming request
        $validatedData = $request->validate([
            'description' => 'required|string',
            'medias.*' => 'nullable',
        ]);

        try {
            $blog = Blog::findOrFail($id);
            $blog->update([
                'description'=> $request->description,
            ]);
            // Handle media if present in the request
            if ($request->hasFile('medias')) {
                //existed file delete
                $existedMedia = Media::where('blog_id', $blog->id)->get();
                foreach ($existedMedia as $media) {
                    File::delete(public_path('assets/img/blogs/'.$media->media));
                    $media->delete();
                }

                foreach ($request->file('medias') as $media) {
                    $mediaName = uniqid('blogs') . '.' . $media->getClientOriginalExtension();
                    $media->move(public_path('assets/img/blogs/'), $mediaName);

                    $ext = $media->getClientOriginalExtension();

                    if($ext == "jpg" || $ext == "png" || $ext == "jpeg" || $ext == "gif" || $ext == "svg"){
                        $type = 1;
                    }else{
                        $command = "ffprobe -v error -show_entries format=duration -of default=noprint_wrappers=1:nokey=1 storage/app/$videoPath";
                        $duration = shell_exec($command);
                        $durationInSeconds = (float) $duration;
                        $threshold = 300; // 5 minutes (adjust according to your criteria)
                        $type = ($durationInSeconds <= $threshold) ? 2 : 3;
                    }

                    // create media and associate it with the blog
                    Media::create([
                        'media' => $mediaName,
                        'type' => $type,
                    ]);
                }
                return response()->json([
                    'message' => 'true',
                    'success' => 'Blog Updated Successfully'
                ],200);
            }
            return response()->json([
                'message' => 'true',
                'success' => 'Blog Updated Successfully'
            ],200);

        }catch (\Exception $e) {
            return response()->json([
                'message' => 'false',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function updateMedia(Request $request, $id){
        $request->validate([
            'media' => 'required'
        ]);
        try{
            $media = Media::find($id);
            //delete existed file first
            File::delete(public_path('assets/img/blogs/'.$media->media));

            $newMedia = $request->file('media');

            $mediaName = uniqid('blogs') . '.' . $newMedia->getClientOriginalExtension();
            $newMedia->move(public_path('assets/img/blogs/'), $mediaName);
            
            $ext = $newMedia->getClientOriginalExtension();

            if($ext == "jpg" || $ext == "png" || $ext == "jpeg" || $ext == "gif" || $ext == "svg"){
                $type = 1;
            }else{
                $command = "ffprobe -v error -show_entries format=duration -of default=noprint_wrappers=1:nokey=1 storage/app/$videoPath";
                $duration = shell_exec($command);
                $durationInSeconds = (float) $duration;
                $threshold = 300; // 5 minutes (adjust according to your criteria)
                $type = ($durationInSeconds <= $threshold) ? 2 : 3;
            }

            $media->update([
                'media' => $mediaName,
                'type' => $type,
                'encoded_url' => ""
            ]);
            return response()->json([
                'message'=> 'true',
                'success' => 'Media Updated Successfully',
            ],200);

        }catch (\Exception $e) {
            return response()->json([
                'message' => 'false',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function deleteMedia($id){
        $media = Media::find($id);
        try{
            //delete existed file first
            File::delete(public_path('assets/img/blogs/'.$media->media));
            $media->delete();
            return response()->json([
                'message'=> 'true',
                'success'=> 'Media Deleted Successfully'
            ],200);
        }catch (\Exception $e) {
            return response()->json([
                'message' => 'false',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function destroy(Blog $blogPost)
    {
        abort_if(Gate::denies('blog_post_delete'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $blogPost->delete();

        return response()->json([
            'message' => 'true',
            'status' => 'Blog Deleted Successfully.'
        ],200);
    }

    public function update(Request $request, $id)
    {
        try {
            // Find the blog post by ID
            $blog = Blog::findOrFail($id);

            // Extract the data from the request
            $data = $request->all();

            // Check if a new image has been uploaded
            $newImage = $request->file('image');

            if ($newImage) {
                $mainFolder = 'blog_images/' . Str::random(); // Modify the folder structure as needed
                $filename = $newImage->getClientOriginalName();

                // Store the new image with specified visibility settings
                $path = Storage::putFileAs(
                    'public/' . $mainFolder,
                    $newImage,
                    $filename,
                    [
                        'visibility' => 'public',
                        'directory_visibility' => 'public',
                    ]
                );

                $data['image'] = URL::to(Storage::url($path));
                $data['image_mime'] = $newImage->getClientMimeType();
                $data['image_size'] = $newImage->getSize();

                // If there is an old image, delete it
                if ($blog->image) {
                    // Extract the relative path from the full URL.
                    $oldImagePath = str_replace(URL::to('/'), '', $blog->image);
                    Storage::delete($oldImagePath);
                }
            }

            // You can add the user_id here if needed
            $data['user_id'] = Auth::user()->id;

            // Update the blog post with the new data
            $blog->update($data);

            // Return a JSON response indicating success
            return response()->json(['message' => 'Blog Updated'], 200);
        } catch (\Exception $e) {
            // Log the error for debugging
            \Log::error($e);

            // Return an error response
            return response()->json(['error' => 'An error occurred during the update.'], 500);
        }
    }

    public function saveImage(UploadedFile $image)
    {
        $path = 'banner_image/' . Str::random();
        //$path = 'images/product_image';

        if (!Storage::exists($path)) {
            Storage::makeDirectory($path, 0755, true);
        }
        if (!Storage::putFileAS('public/' . $path, $image, $image->getClientOriginalName())) {
            throw new \Exception("Unable to save file \"{$image->getClientOriginalName()}\"");
        }

        return $path . '/' . $image->getClientOriginalName();
    }
}