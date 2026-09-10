<?php

namespace App\Actions;

use App\Models\Post;
use App\Models\PostImage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PurgeStalePosts
{
    private const RETENTION_DAYS = 7;

    private const DEFAULT_BATCH_SIZE = 50;

    public function __invoke(int $limit = self::DEFAULT_BATCH_SIZE): int
    {
        $purged = 0;
        $cutoff = now()->subDays(self::RETENTION_DAYS);

        $trashed = Post::onlyTrashed()
            ->where("deleted_at", "<=", $cutoff)
            ->orderBy("deleted_at")
            ->limit($limit)
            ->get();

        foreach($trashed as $post) {
            if( $this->purgePost($post) ) {
                $purged++;
            }
        }

        $remaining = $limit - $purged;

        if( $remaining > 0 ) {
            $drafts = Post::query()
                ->where("status", Post::STATUS_DRAFT)
                ->where("updated_at", "<=", $cutoff)
                ->orderBy("updated_at")
                ->limit($remaining)
                ->get();

            foreach($drafts as $post) {
                if( $this->purgePost($post) ) {
                    $purged++;
                }
            }
        }

        if( $purged > 0 ) {
            Log::info("PurgeStalePosts: {$purged} post(s) removido(s) permanentemente.");
        }

        return $purged;
    }

    private function purgePost(Post $post): bool
    {
        try {
            $this->deletePostFiles($post);
            $post->forceDelete();

            return true;
        } catch (\Throwable $e) {
            Log::error("PurgeStalePosts: falha ao remover post {$post->id}: {$e->getMessage()}");

            return false;
        }
    }

    private function deletePostFiles(Post $post): void
    {
        $post->images()->each(function (PostImage $image) {
            Storage::disk("local")->delete($image->storagePath());
            $image->delete();
        });

        $this->deleteStoredPath($post->thumbnail_path);
    }

    private function deleteStoredPath(?string $path): void
    {
        if( empty($path) || Str::startsWith($path, ["http://", "https://"]) ) {
            return;
        }

        $storage_path = ltrim($path, "/");

        if( Storage::disk("local")->exists($storage_path) ) {
            Storage::disk("local")->delete($storage_path);
        }
    }
}
