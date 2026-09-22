<?php

namespace App\Services\Notifications;

use App\Models\Post;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class DiscordWebhookSender
{
    public function send(string $webhook_url, array $payload): void
    {
        Http::timeout(15)
            ->post($webhook_url, $payload)
            ->throw();
    }

    public function buildPostPayload(Post $post): array
    {
        $url = route("blog.show", [
            $post->category->slug,
            $post->slug,
        ]);

        $embed = [
            "title" => Str::limit($post->title, 256, ""),
            "description" => $post->excerpt,
            "url" => $url,
        ];

        if( $thumbnail_url = $this->resolveThumbnailUrl($post->thumbnail_path) ) {
            $embed["thumbnail"] = [
                "url" => $thumbnail_url,
            ];
        }

        return [
            "embeds" => [ $embed ],
        ];
    }

    private function resolveThumbnailUrl(?string $path): ?string
    {
        if( empty($path) ) {
            return null;
        }

        if( Str::startsWith($path, ["http://", "https://"]) ) {
            return $path;
        }

        return url($path);
    }
}
