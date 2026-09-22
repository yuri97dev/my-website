<?php

namespace App\Services\Notifications\Discord;

use App\Models\Post;
use App\Services\Notifications\DiscordWebhookSender;

class PostPublishedPayloadBuilder
{
    public function __construct(
        private DiscordWebhookSender $discord,
    ) {}

    public function build(
        Post $post,
        ?string $title = null,
        ?string $description = null,
    ): array
    {
        return $this->discord->buildEmbedPayload(
            title: $title ?? $post->title,
            description: $description ?? $post->excerpt,
            url: route("blog.show", [
                $post->category->slug,
                $post->slug,
            ]),
            thumbnail_url: $post->thumbnail_path,
        );
    }
}
