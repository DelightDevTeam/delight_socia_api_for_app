<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\VideoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class TestController extends Controller
{
	public function index(Request $request)
	{
		// return file_get_contents(public_path("test.txt"));
		$media = $request->file("video");
		// return $media = $request->file("video")->storeAs("test", "test.jpg", "upload");

		$file_path = $media->store("assets", "upload");

		return $length = VideoService::getVideoInfo($file_path);
	}
}
