<?php

namespace App\Jobs;

use App\Models\NotificationDelivery;
use App\Models\NotificationProvider;
use App\Models\Post;
use App\Services\Notifications\DiscordWebhookSender;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendPostPublishedNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(
        protected int $post_id,
    ) {
        $this->onQueue("notifications");
    }

    public function handle(DiscordWebhookSender $discord): void
    {
        $post = Post::query()
            ->with("category:id,slug,name")
            ->find($this->post_id);

        if( !$post || $post->category === null ) {
            return;
        }

        $provider = NotificationProvider::query()
            ->enabled()
            ->channel(NotificationProvider::CHANNEL_DISCORD)
            ->key($post->category->slug)
            ->first();

        if( !$provider ) {
            return;
        }

        $webhook_url = $provider->config["webhook_url"] ?? null;

        if( empty($webhook_url) ) {
            Log::warning("SendPostPublishedNotificationJob: provider {$provider->id} sem webhook_url configurada.");
            return;
        }

        $payload = $discord->buildPostPayload($post);

        $delivery = NotificationDelivery::create([
            "channel" => NotificationProvider::CHANNEL_DISCORD,
            "status" => NotificationDelivery::STATUS_PENDING,
            "subject" => $post->title,
            "body" => $post->excerpt,
            "payload" => [
                "provider_id" => $provider->id,
                "provider_key" => $provider->key,
                "post_id" => $post->id,
                "post_url" => $payload["embeds"][0]["url"] ?? null,
            ],
            "notifiable_type" => Post::class,
            "notifiable_id" => $post->id,
        ]);

        try {
            $discord->send($webhook_url, $payload);

            $delivery->update([
                "status" => NotificationDelivery::STATUS_SENT,
                "sent_at" => now(),
                "attempts" => $this->attempts(),
            ]);
        } catch (\Throwable $e) {
            $delivery->update([
                "status" => NotificationDelivery::STATUS_FAILED,
                "failed_at" => now(),
                "error_message" => $e->getMessage(),
                "attempts" => $this->attempts(),
            ]);

            throw $e;
        }
    }
}
