<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\M3u8Convertor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class TestController extends Controller
{
    const TMP_UPLOADS_PATH = "app/tmp_uploads";

    const TMP_VIDEO_UPLOADS_PATH = self::TMP_UPLOADS_PATH . "/videos";

    public function index(Request $request)
    {
        $request->validate([
            "media" => "required|file",
        ]);

        $media =  $request->file("media");

        $s3_url = (new M3u8Convertor)->convert($media);

        return $s3_url;
    }

    private function generateVideoPath($file_path)
    {
        return self::TMP_VIDEO_UPLOADS_PATH  . "/" . $file_path;
    }

    private function generateFullPath($folder_name, $full_file_name)
    {
        return $folder_name . "/" . $full_file_name;
    }

    private function generateFolderName()
    {
        return now()->getTimestamp() . "-" . $this->generateRandom();
    }

    private function generateFilename()
    {
        return $this->generateRandom(40);
    }

    private function generateRandom($length = 26)
    {
        return Str::random($length);
    }
}
