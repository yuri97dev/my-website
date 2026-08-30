<?php

namespace App\Jobs;

use App\Actions\OptimizeStoredImage;
use App\Models\PostImage;
use App\Models\ScrapedArticleImage;
use App\Models\SgcCustomerAttachment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class OptimizeImageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public int $timeout = 120;

    public function __construct(
        public PostImage|ScrapedArticleImage|SgcCustomerAttachment $image,
    ) {
        $this->onQueue("images");
    }

    public function handle(OptimizeStoredImage $optimizer): void
    {
        $this->image->markOptimizationAttempt();

        $result = $optimizer($this->image->storagePath());

        $this->image->applyOptimizationResult($result);
        $this->image->markOptimized($result);

        if( $result === null ) return;

        Log::info("OptimizeImageJob: optimized {$this->image->getMorphClass()}:{$this->image->getKey()}", [
            "original_size_bytes" => $result->original_size_bytes,
            "optimized_size_bytes" => $result->optimized_size_bytes,
            "savings_ratio" => round($result->savingsRatio() * 100, 2),
        ]);
    }
}
