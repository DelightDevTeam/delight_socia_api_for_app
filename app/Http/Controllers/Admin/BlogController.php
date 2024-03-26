<?php

namespace App\Http\Controllers\Admin;

use App\Models\Admin\Blog;
use App\Models\Admin\Media;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use App\Http\Requests\BlogRequest;
use App\Http\Requests\BlogImageRequest;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;



class BlogController extends Controller
{
    public function index()
    {
        $blogs = Blog::with(['users', 'medias'])->latest()->get();
        // return $blogs;
        return view('Admin.blogs.index', compact('blogs'));
    }

    public function create()
    {
        return view('Admin.blogs.create');
    }

    public function saveToken(Request $request)
    {
        $user = Auth::user();
        $user->update([
            'device_token'=>$request->token
        ]);
        return response()->json(['token saved successfully.']);
    }

    public function store(BlogRequest $request)
    {
        $blog = Blog::create([
            'description' => $request->description,
            'user_id' => Auth::user()->id,
        ]);

        if ($request->hasFile('medias')) {
            $medias = $request->file('medias');

            foreach ($medias as $media) {
                $mediaName = uniqid('blogs') . '.' . $media->getClientOriginalExtension();
                $media->move(public_path('assets/img/blogs/'), $mediaName);

                Media::create([
                    'blog_id' => $blog->id,
                    'media' => $mediaName,
                ]);
            }

            // return redirect('/admin/blogs')->with('success', "Blog Media Created");
        }
        $blogDesc = Str::limit($blog->description, 100, '...');

        $firebaseToken = User::whereNotNull('device_token')->pluck('device_token')->all();
        // $firebaseToken = User::all();
        // return $firebaseToken;

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

        return redirect('/admin/blogs')->with('toast_success', "Blog Created");
    }

    public function view($id)
    {
        $blog = Blog::withCount(['likes', 'comments'])->where('id', $id)->first();
        // return $blog;
        return view('Admin.blogs.view', compact('blog'));
    }

    public function edit($id)
    {
        $blog = Blog::find($id);
        return view('Admin.blogs.edit', compact('blog'));
    }

    public function mediaUpdate(Request $request, $id){
        $media = Media::find($id);
        if(!$media){
            return redirect()->back()->with('error','Media Not Found');
        }
        $request->validate([
            'media' => 'required'
        ]);
        //delete file
        File::delete(public_path('assets/img/blogs/'.$media->media));

        $multimedia = $request->file('media');
        $mediaName = uniqid('blogs') . '.' . $multimedia->getClientOriginalExtension();
        $multimedia->move(public_path('assets/img/blogs/'), $mediaName);

        $media->update([
            'media' => $mediaName,
        ]);
        return redirect()->back()->with('success','Media Updated');
    }

    public function mediaDelete($id){
        $media = Media::find($id);
        if(!$media){
            return redirect()->back()->with('error','Media Not Found');
        }
        //delete file
        File::delete(public_path('assets/img/blogs/'.$media->media));
        $media->delete();
        return redirect()->back()->with('success','Media Delete');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'description' => 'required|string',
        ]);

        $blog = Blog::findOrFail($id);
        if(!$blog){
            return redirect()->back()->with('error','Blog Not Found');
        }

        $blog->update([
            'description' => $request->description,
            'user_id' => Auth::user()->id,
        ]);

        if ($request->hasFile('medias')) {
            $medias = $request->file('medias');

            //file delete
            $existedMedia = Media::where('blog_id', $blog->id)->get();
            foreach ($existedMedia as $media) {
                File::delete(public_path('assets/img/blogs/'.$media->media));
                $media->delete();
            }

            foreach ($medias as $media) {
                $mediaName = uniqid('blogs') . '.' . $media->getClientOriginalExtension();
                $media->move(public_path('assets/img/blogs/'), $mediaName);

                Media::create([
                    'blog_id' => $blog->id,
                    'media' => $mediaName,
                ]);
            }

            return redirect('/admin/blogs')->with('success', "Blog Media Updated");
        }
        return redirect('/admin/blogs')->with('success', "Blog Media Updated");
    }

    public function delete(Request $request)
    {
        $id = $request->id;
        $blog = Blog::find($id);
        if(!$blog){
            return redirect()->back()->with('error', "Blog Not Found!");
        }
        $medias = Media::where('blog_id', $blog->id)->get();
        foreach($medias as $media){
            File::delete(public_path('assets/img/blogs/'.$media->media));
        }
        Blog::destroy($id);
        return redirect('/admin/blogs/')->with('success', "Blog Removed.");
    }

    public function saveImage(UploadedFile $image)
    {
        $path = 'blog_image/' . Str::random();

        if (!Storage::exists($path)) {
            Storage::makeDirectory($path, 0755, true);
        }

        if (!Storage::putFileAs('public/' . $path, $image, $image->getClientOriginalName())) {
            throw new \Exception("Unable to save file \"{$image->getClientOriginalName()}\"");
        }

        return $path . '/' . $image->getClientOriginalName();
    }
}
