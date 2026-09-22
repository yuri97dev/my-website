<?php

namespace App\Jobs;

use App\Models\NotificationDelivery;
use App\Models\NotificationProvider;
use App\Models\Post;
use App\Services\Notifications\Discord\PostPublishedDiscordNotifier;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ResendNotificationDeliveryJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(
        protected int $delivery_id,
        protected ?string $subject = null,
        protected ?string $body = null,
    ) {
        $this->onQueue("notifications");
    }

    public function handle(PostPublishedDiscordNotifier $notifier): void
    {
        $original = NotificationDelivery::query()->find($this->delivery_id);

        if( !$original ) {
            return;
        }

        $provider_id = $original->payload["provider_id"] ?? null;

        if( !$provider_id ) {
            Log::warning("ResendNotificationDeliveryJob: entrega {$original->id} sem provider_id no payload.");
            return;
        }

        $provider = NotificationProvider::query()->find($provider_id);

        if( !$provider || !$provider->is_enabled ) {
            Log::warning("ResendNotificationDeliveryJob: provider {$provider_id} indisponível.");
            return;
        }

        $post = $this->resolvePost($original);

        if( !$post || $post->category === null ) {
            Log::warning("ResendNotificationDeliveryJob: entrega {$original->id} sem post associado.");
            return;
        }

        $subject = filled($this->subject) ? $this->subject : $original->subject;
        $body = filled($this->body) ? $this->body : $original->body;

        $notifier->deliver(
            $post,
            $provider,
            $this->attempts(),
            $subject,
            $body,
            ["resent_from_id" => $original->id],
        );
    }

    private function resolvePost(NotificationDelivery $delivery): ?Post
    {
        if( $delivery->notifiable_type === Post::class && $delivery->notifiable_id ) {
            return Post::query()
                ->with("category:id,slug,name")
                ->find($delivery->notifiable_id);
        }

        $post_id = $delivery->payload["post_id"] ?? null;

        if( !$post_id ) {
            return null;
        }

        return Post::query()
            ->with("category:id,slug,name")
            ->find($post_id);
    }
}
