<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\VideoService;
use Illuminate\Http\Request;

class TestController extends Controller
{
    public function index(Request $request)
    {
        $media =  $request->file("video");

        $file_path = $media->store("assets/img/blogs", 'upload');

        return $length = VideoService::getPlaytimeSeconds($file_path);
    }
}
