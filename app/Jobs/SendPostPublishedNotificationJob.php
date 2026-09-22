<?php

namespace App\Jobs;

use App\Models\NotificationProvider;
use App\Models\Post;
use App\Services\Notifications\Discord\PostPublishedDiscordNotifier;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendPostPublishedNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(
        protected int $post_id,
    ) {
        $this->onQueue("notifications");
    }

    public function handle(PostPublishedDiscordNotifier $notifier): void
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

        $notifier->deliver($post, $provider, $this->attempts());
    }
}
