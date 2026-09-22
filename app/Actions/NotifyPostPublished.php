<?php

namespace App\Actions;

use App\Jobs\SendPostPublishedNotificationJob;
use App\Models\Post;

class NotifyPostPublished
{
    public function __invoke(Post $post): void
    {
        SendPostPublishedNotificationJob::dispatch($post->id);
    }
}
