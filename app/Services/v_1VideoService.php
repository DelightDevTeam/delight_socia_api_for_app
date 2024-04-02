<?php

namespace App\Services;

use Exception;
use Owenoj\LaravelGetId3\GetId3;

class VideoService
{
    public static function getPlaytimeSeconds(string $file_path, string $disk = 'upload')
    {
        try {
            $track = GetId3::fromDiskAndPath($disk, $file_path);

            return $track->getPlaytimeSeconds();
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }
}
