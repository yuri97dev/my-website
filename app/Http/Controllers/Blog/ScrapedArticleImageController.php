<?php

namespace App\Http\Controllers\Blog;

use App\Http\Controllers\Controller;
use App\Models\ScrapedArticleImage;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class ScrapedArticleImageController extends Controller
{
    public function show(ScrapedArticleImage $scrapedArticleImage)
    {
        abort_unless(
            Storage::disk("local")->exists($scrapedArticleImage->path),
            Response::HTTP_NOT_FOUND,
        );

        return response()->file(Storage::disk("local")->path($scrapedArticleImage->path));
    }
}
