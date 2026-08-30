<?php

namespace App\Concerns;

use App\DataTransferObjects\OptimizationResult;
use App\Jobs\OptimizeImageJob;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;

trait OptimizesStoredImage
{
    public const MAX_OPTIMIZATION_ATTEMPTS = 3;

    public function dispatchImageOptimization(): void
    {
        OptimizeImageJob::dispatch($this);
    }

    public function storagePath(): string
    {
        return ltrim((string) $this->path, "/");
    }

    public function markOptimizationAttempt(): void
    {
        $this->increment("optimization_attempts");
    }

    public function markOptimized(?OptimizationResult $result = null): void
    {
        $attributes = [
            "optimized_at" => now(),
        ];

        if( $result !== null ) {
            if( is_null($this->original_size_bytes) ) {
                $attributes["original_size_bytes"] = $result->original_size_bytes;
            }

            $attributes["size_bytes"] = $result->optimized_size_bytes;
        } elseif( is_null($this->size_bytes) ) {
            $path = $this->storagePath();

            if( Storage::disk("local")->exists($path) ) {
                $attributes["size_bytes"] = Storage::disk("local")->size($path);

                if( is_null($this->original_size_bytes) ) {
                    $attributes["original_size_bytes"] = $attributes["size_bytes"];
                }
            }
        }

        $this->forceFill($attributes)->save();
    }

    public function applyOptimizationResult(?OptimizationResult $result): void
    {
        if( $result === null ) {
            return;
        }

        $this->forceFill([
            "path" => $this->formatStoredPath($result->path),
            ...$this->extraFieldsAfterOptimization($result),
        ])->save();
    }

    /**
     * @return array<string, mixed>
     */
    protected function extraFieldsAfterOptimization(?OptimizationResult $result): array
    {
        return [];
    }

    protected function formatStoredPath(string $path): string
    {
        return $this->usesLeadingSlashInPath()
            ? "/" . ltrim($path, "/")
            : ltrim($path, "/");
    }

    protected function usesLeadingSlashInPath(): bool
    {
        return true;
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopePendingOptimization(Builder $query): Builder
    {
        return $query
            ->whereNull("optimized_at")
            ->where("optimization_attempts", "<", self::MAX_OPTIMIZATION_ATTEMPTS);
    }
}
