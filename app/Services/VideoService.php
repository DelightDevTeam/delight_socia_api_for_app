<?php 

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Storage;
use Owenoj\LaravelGetId3\GetId3;

class VideoService
{
    public static function getVideoInfo(string $file_path, string $disk = 'upload')
    {
        try {
            $track = GetId3::fromDiskAndPath($disk, $file_path);

            if (!$track) {
                throw new Exception('Failed to get video info.');
            }

            $playtimeSeconds = $track->getPlaytimeSeconds();
            $fileSizeBytes = Storage::disk($disk)->size($file_path);

            return [
                'playtimeSeconds' => $playtimeSeconds,
                'fileSizeBytes' => $fileSizeBytes,
            ];
        } catch (Exception $e) {
            return [
                'error' => $e->getMessage(),
            ];
        }
    }
}