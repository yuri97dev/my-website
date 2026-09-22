<?php

namespace App\Services\Notifications\Discord;

use App\Models\NotificationDelivery;
use App\Models\NotificationProvider;
use App\Models\Post;
use App\Services\Notifications\DiscordWebhookSender;
use Illuminate\Support\Facades\Log;

class PostPublishedDiscordNotifier
{
    public function __construct(
        private DiscordWebhookSender $discord,
        private PostPublishedPayloadBuilder $payload_builder,
    ) {}

    public function deliver(
        Post $post,
        NotificationProvider $provider,
        int $attempts,
        ?string $subject = null,
        ?string $body = null,
        array $extra_payload = [],
    ): ?NotificationDelivery
    {
        $webhook_url = $provider->config["webhook_url"] ?? null;

        if( empty($webhook_url) ) {
            Log::warning("PostPublishedDiscordNotifier: provider {$provider->id} sem webhook_url configurada.");
            return null;
        }

        $subject ??= $post->title;
        $body ??= $post->excerpt;

        $payload = $this->payload_builder->build($post, $subject, $body);

        $delivery = NotificationDelivery::create([
            "channel" => $provider->channel,
            "status" => NotificationDelivery::STATUS_PENDING,
            "subject" => $subject,
            "body" => $body,
            "payload" => [
                "provider_id" => $provider->id,
                "provider_key" => $provider->key,
                "post_id" => $post->id,
                "post_url" => $payload["embeds"][0]["url"] ?? null,
                ...$extra_payload,
            ],
            "notifiable_type" => Post::class,
            "notifiable_id" => $post->id,
        ]);

        try {
            $this->discord->send($webhook_url, $payload);

            $delivery->update([
                "status" => NotificationDelivery::STATUS_SENT,
                "sent_at" => now(),
                "attempts" => $attempts,
            ]);
        } catch (\Throwable $e) {
            $delivery->update([
                "status" => NotificationDelivery::STATUS_FAILED,
                "failed_at" => now(),
                "error_message" => $e->getMessage(),
                "attempts" => $attempts,
            ]);

            throw $e;
        }

        return $delivery;
    }
}
