<?php

namespace App\Services\Images;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class ImageOptimizer
{
    private ImageManager $manager;

    public function __construct()
    {
        $this->manager = new ImageManager(new Driver());
    }

    public function storeOptimized(
        UploadedFile $file,
        string $directory,
        int $maxWidth = 1600,
        int $quality = 82
    ): string {
        $image = $this->manager->read($file->getPathname());

        // Scale down large images while preserving aspect ratio
        $image->scaleDown(width: $maxWidth);

        $extension = strtolower($file->getClientOriginalExtension() ?: 'jpg');
        $baseName = pathinfo($file->hashName(), PATHINFO_FILENAME);

        switch ($extension) {
            case 'png':
                $encoded = $image->toPng();
                $finalExt = 'png';
                break;
            case 'webp':
                $encoded = $image->toWebp($quality);
                $finalExt = 'webp';
                break;
            case 'jpg':
            case 'jpeg':
            default:
                $encoded = $image->toJpeg($quality);
                $finalExt = 'jpg';
                break;
        }

        $path = trim($directory, '/').'/'.$baseName.'.'.$finalExt;

        Storage::disk('public')->put($path, $encoded->toString());

        return $path;
    }
}
