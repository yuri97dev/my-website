<?php

namespace App\Actions;

use App\Jobs\OptimizeImageJob;
use App\Models\PostImage;
use App\Models\ScrapedArticleImage;
use App\Models\SgcCustomerAttachment;

class OptimizePendingImages
{
    private const DEFAULT_BATCH_SIZE = 50;

    /** @var list<class-string<PostImage|ScrapedArticleImage|SgcCustomerAttachment>> */
    private const MODELS = [
        PostImage::class,
        ScrapedArticleImage::class,
        SgcCustomerAttachment::class,
    ];

    public function __invoke(int $limit = self::DEFAULT_BATCH_SIZE, bool $sync = false): int
    {
        $dispatched = 0;

        foreach(self::MODELS as $model) {
            $remaining = $limit - $dispatched;

            if( $remaining <= 0 ) {
                break;
            }

            $images = $model::query()
                ->pendingOptimization()
                ->orderBy("id")
                ->limit($remaining)
                ->get();

            foreach($images as $image) {
                if( $sync ) {
                    OptimizeImageJob::dispatchSync($image);
                } else {
                    OptimizeImageJob::dispatch($image);
                }

                $dispatched++;
            }
        }

        return $dispatched;
    }
}
