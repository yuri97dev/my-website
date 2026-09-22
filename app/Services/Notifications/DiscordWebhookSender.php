<?php

namespace App\Services\Notifications;

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

    public function buildEmbedPayload(
        string $title,
        ?string $description = null,
        ?string $url = null,
        ?string $thumbnail_url = null,
    ): array
    {
        $embed = [
            "title" => Str::limit($title, 256, ""),
        ];

        if( filled($description) ) {
            $embed["description"] = $description;
        }

        if( filled($url) ) {
            $embed["url"] = $url;
        }

        if( $resolved_thumbnail = $this->resolveThumbnailUrl($thumbnail_url) ) {
            $embed["thumbnail"] = [
                "url" => $resolved_thumbnail,
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
