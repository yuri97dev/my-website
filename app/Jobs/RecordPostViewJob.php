<?php

namespace App\Jobs;

use App\Models\Post;
use App\Models\PostView;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class RecordPostViewJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        protected int $post_id,
        protected string $visitor_hash,
        protected ?int $user_id = null,
        protected ?string $referrer = null,
    ) {
        $this->onQueue("post-views");
    }

    public function handle(): void
    {
        DB::transaction(function () {
            PostView::create([
                "post_id" => $this->post_id,
                "visitor_hash" => $this->visitor_hash,
                "user_id" => $this->user_id,
                "referrer" => $this->referrer,
                "viewed_at" => now(),
            ]);

            Post::query()
                ->whereKey($this->post_id)
                ->increment("views_count");
        });
    }
}
