<?php

namespace App\Actions;

use App\Helpers\HtmlFormatter;
use App\Helpers\ScrapeHttp;
use App\Models\ScrapedArticle;
use App\Models\ScrapedArticleImage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DownloadScrapedArticleImages
{
    private const MAX_BYTES = 10 * 1024 * 1024;

    private const MIME_EXTENSIONS = [
        "image/jpeg" => "jpg",
        "image/jpg" => "jpg",
        "image/png" => "png",
        "image/gif" => "gif",
        "image/webp" => "webp",
        "image/avif" => "avif",
        "image/bmp" => "bmp",
        "image/svg+xml" => "svg",
    ];

    /** @var array<string, string|null> */
    private array $resolved_urls = [];

    public function __invoke(ScrapedArticle $article): void
    {
        $content = HtmlFormatter::rewriteImageSources(
            $article->content,
            fn(string $src) => $this->resolveImageSource($article, $src),
        );

        $article->content = $content;

        $article->save();
    }

    private function resolveImageSource(ScrapedArticle $article, string $src): ?string
    {
        if( !$this->isExternalHttpUrl($src) ) {
            return null;
        }

        if( array_key_exists($src, $this->resolved_urls) ) {
            return $this->resolved_urls[$src];
        }

        $resolved = $this->downloadImage($article, $src);

        $this->resolved_urls[$src] = $resolved;

        return $resolved;
    }

    private function isExternalHttpUrl(string $url): bool
    {
        if( !preg_match("/^https?:\/\//i", $url) ) {
            return false;
        }

        $host = parse_url($url, PHP_URL_HOST);
        $app_host = parse_url(config("app.url"), PHP_URL_HOST);

        if( empty($host) || empty($app_host) ) {
            return true;
        }

        return strcasecmp($host, $app_host) !== 0;
    }

    private function downloadImage(ScrapedArticle $article, string $src): ?string
    {
        try {

            $response = ScrapeHttp::client($article->source)
                ->timeout(20)
                ->withHeaders([
                    "Accept" => "image/*,*/*;q=0.8",
                ])
                ->get($src);

            if( !$response->successful() ) {
                throw new \RuntimeException("HTTP {$response->status()}");
            }

            $content_type = $this->normalizeContentType(
                $response->header("Content-Type"),
            );

            if( !$this->isAllowedImageType($content_type) ) {
                throw new \RuntimeException("Invalid content type: {$content_type}");
            }

            $body = $response->body();

            if( strlen($body) === 0 ) {
                throw new \RuntimeException("Empty response body");
            }

            if( strlen($body) > self::MAX_BYTES ) {
                throw new \RuntimeException("Image exceeds max size");
            }

            $extension = $this->resolveExtension($content_type, $src);
            $filename = Str::uuid()->toString() . "." . $extension;
            $storage_path = "scraped-articles/images/" . date("Y/m/d") . "/" . $filename;

            Storage::disk("local")->put($storage_path, $body);

            $image = ScrapedArticleImage::create([
                "scraped_article_id" => $article->id,
                "path" => "/" . ltrim($storage_path, "/"),
            ]);

            return route("blog.scraped-images.show", $image->id, false);

        } catch (\Throwable $e) {

            Log::warning("DownloadScrapedArticleImages: failed to download image {$src}: {$e->getMessage()}");
            return null;

        }
    }

    private function normalizeContentType(?string $content_type): string
    {
        if( empty($content_type) ) {
            return "";
        }

        return strtolower(trim(explode(";", $content_type)[0]));
    }

    private function isAllowedImageType(string $content_type): bool
    {
        return array_key_exists($content_type, self::MIME_EXTENSIONS);
    }

    private function resolveExtension(string $content_type, string $src): string
    {
        if( array_key_exists($content_type, self::MIME_EXTENSIONS) ) {
            return self::MIME_EXTENSIONS[$content_type];
        }

        $path = parse_url($src, PHP_URL_PATH) ?: "";
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        if( in_array($extension, array_values(self::MIME_EXTENSIONS), true) ) {
            return $extension;
        }

        return "jpg";
    }
}
