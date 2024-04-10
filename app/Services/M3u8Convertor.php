<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class M3u8Convertor
{
    const TMP_UPLOADS_PATH = "app/tmp_uploads";

    const TMP_VIDEO_UPLOADS_PATH = self::TMP_UPLOADS_PATH . "/videos";

    public function convert($media)
    {
        $folder_name = $this->generateFolderName();

        $filename = $this->generateFilename();

        $file_path =  $this->generateFullPath(
            $folder_name,
            $filename . "." . $media->extension()
        );

        $media->storeAs($this->generateVideoPath($file_path), ["disk" => "tmp_upload"]);

        $input = storage_path($this->generateVideoPath($file_path));

        $m3u8_file_path =  $this->generateFullPath(
            $folder_name,
            $filename . ".m3u8"
        );

        Storage::disk("tmp_upload")->deleteDirectory($folder_name);

        $output = storage_path($this->generateVideoPath($m3u8_file_path));

        exec("ffmpeg -i $input -hls_time 15 -hls_list_size 0 $output");

        $output_files = Storage::disk("tmp_upload")->files($this->generateVideoPath($folder_name));

        $output_files = collect($output_files)->filter(function ($output_file) {
            return str($output_file)->endsWith(".ts") || str($output_file)->endsWith(".m3u8");
        })->values()->toArray();

        $m3u8_file = collect($output_files)->filter(function ($output_file) {
            return str($output_file)->endsWith(".m3u8");
        })->first();

        if (!$m3u8_file) {
            return response()->json(["error" => "m3u8 file not found"], 404);
        }

        unset($output_files[array_search($m3u8_file, $output_files)]);

        $s3_m3u8_file_contents = file_get_contents(storage_path($m3u8_file));

        foreach ($output_files as $index => $output_file) {
            $s3_path = "public_uploads" . str($output_file)->replace(self::TMP_UPLOADS_PATH, "");

            Storage::disk("s3")->put(
                $s3_path,
                file_get_contents(storage_path($output_file)),
            );

            if (str($output_file)->endsWith(".ts")) {
                $output_file_name = collect(explode("/", $output_file))->last();

                $s3_m3u8_file_contents = str($s3_m3u8_file_contents)->replace($output_file_name, Storage::disk("s3")
                    ->url($s3_path));
            }
        }

        $s3_m3u8_file_path = "public_uploads" . str($m3u8_file)->replace(self::TMP_UPLOADS_PATH, "");

        Storage::disk("s3")->put(
            $s3_m3u8_file_path,
            $s3_m3u8_file_contents,
        );

        $s3_m3u8_file_url = Storage::disk("s3")->url($s3_m3u8_file_path);
        
        // exec("rm -rf " . storage_path($this->generateVideoPath($folder_name)));

        return $s3_m3u8_file_url;
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
