<?php

namespace App\Actions;

use App\DataTransferObjects\OptimizationResult;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Encoders\WebpEncoder;
use Intervention\Image\ImageManager;

class OptimizeStoredImage
{
    private const MAX_MEGAPIXELS = 25_000_000;

    private const MIN_SAVINGS_RATIO = 0.05;

    private const MAX_DIMENSION = 2560;

    private const WEBP_QUALITY = 82;

    /** @var list<string> */
    private const SKIP_MIME_TYPES = [
        "image/gif",
        "image/svg+xml",
    ];

    private ImageManager $manager;

    public function __construct()
    {
        $this->manager = new ImageManager(new Driver());
    }

    public function __invoke(string $path): ?OptimizationResult
    {
        $path = ltrim($path, "/");

        if( $path === "" || !Storage::disk("local")->exists($path) ) {
            return null;
        }

        $absolute_path = Storage::disk("local")->path($path);
        $original_bytes = Storage::disk("local")->size($path);

        if( $original_bytes <= 0 ) {
            return null;
        }

        $mime_type = $this->detectMimeType($absolute_path);

        if( !$this->isOptimizableMimeType($mime_type) ) {
            return null;
        }

        if( $this->isAlreadyWebp($path, $mime_type) && $original_bytes <= 120 * 1024 ) {
            return null;
        }

        try {
            $image = $this->manager->decodePath($absolute_path);

            $width = $image->width();
            $height = $image->height();

            if( ($width * $height) > self::MAX_MEGAPIXELS ) {
                return null;
            }

            $image->scaleDown(self::MAX_DIMENSION, self::MAX_DIMENSION);

            $encoded = $image->encode(new WebpEncoder(quality: self::WEBP_QUALITY));
            $optimized_bytes = $encoded->size();

            if( $optimized_bytes >= $original_bytes ) {
                return null;
            }

            $savings_ratio = 1 - ($optimized_bytes / $original_bytes);

            if( $savings_ratio < self::MIN_SAVINGS_RATIO ) {
                return null;
            }

            $optimized_path = $this->buildOptimizedPath($path);

            Storage::disk("local")->put($optimized_path, $encoded->toString());

            if( $optimized_path !== $path ) {
                Storage::disk("local")->delete($path);
            }

            return new OptimizationResult(
                path: $optimized_path,
                original_size_bytes: $original_bytes,
                optimized_size_bytes: $optimized_bytes,
                converted: true,
            );
        } catch (\Throwable $e) {
            Log::warning("OptimizeStoredImage: failed to optimize {$path}: {$e->getMessage()}");

            return null;
        }
    }

    private function detectMimeType(string $absolute_path): string
    {
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime_type = $finfo->file($absolute_path);

        return is_string($mime_type) ? strtolower($mime_type) : "";
    }

    private function isOptimizableMimeType(string $mime_type): bool
    {
        if( $mime_type === "" || !Str::startsWith($mime_type, "image/") ) {
            return false;
        }

        return !in_array($mime_type, self::SKIP_MIME_TYPES, true);
    }

    private function isAlreadyWebp(string $path, string $mime_type): bool
    {
        return $mime_type === "image/webp"
            || str_ends_with(strtolower($path), ".webp");
    }

    private function buildOptimizedPath(string $path): string
    {
        $directory = pathinfo($path, PATHINFO_DIRNAME);
        $filename = pathinfo($path, PATHINFO_FILENAME);

        if( $directory === "." || $directory === "" ) {
            return "{$filename}.webp";
        }

        return "{$directory}/{$filename}.webp";
    }
}
