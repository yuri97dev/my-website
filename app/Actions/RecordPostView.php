<?php

namespace App\Actions;

use App\Helpers\UserAgent;
use App\Jobs\RecordPostViewJob;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Str;

class RecordPostView
{
    private const DEDUP_TTL_SECONDS = 86400;

    public function __invoke(Post $post, Request $request): bool
    {
        if( !$this->shouldRecord($post, $request) ) {
            return false;
        }

        [ $visitor_hash, $visitor_id ] = $this->resolveVisitor($request);

        $cache_key = "post-view:{$post->id}:{$visitor_hash}";

        if( !Cache::add($cache_key, true, self::DEDUP_TTL_SECONDS) ) {
            return false;
        }

        if( $visitor_id ) {
            Cookie::queue(cookie(
                name: "visitor_id",
                value: $visitor_id,
                minutes: 60 * 24 * 365,
                httpOnly: true,
                sameSite: "lax",
            ));
        }

        RecordPostViewJob::dispatch(
            post_id: $post->id,
            visitor_hash: $visitor_hash,
            user_id: $request->user()?->id,
            referrer: Str::limit($request->headers->get("referer"), 500),
        );

        return true;
    }

    private function shouldRecord(Post $post, Request $request): bool
    {
        if( $post->status !== Post::STATUS_PUBLISHED ) {
            return false;
        }

        if( $post->published_at === null || $post->published_at->isFuture() ) {
            return false;
        }

        if( $request->user()?->isSystemAdmin() ) {
            return false;
        }

        $parsed = UserAgent::parse($request->userAgent());

        return ($parsed["device"] ?? null) !== "bot";
    }

    private function resolveVisitor(Request $request): array
    {
        if( $user_id = $request->user()?->id ) {
            return [hash("sha256", "user:{$user_id}"), null];
        }

        $visitor_id = $request->cookie("visitor_id");

        if( !$visitor_id ) {
            $visitor_id = (string) Str::uuid();
            return [hash("sha256", "visitor:{$visitor_id}"), $visitor_id];
        }

        return [hash("sha256", "visitor:{$visitor_id}"), null];
    }
}
