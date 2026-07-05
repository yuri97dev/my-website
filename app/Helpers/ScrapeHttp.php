<?php

namespace App\Helpers;

use App\Models\ArticleSource;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

class ScrapeHttp
{
    /** @var list<array{User-Agent: string, Accept-Language: string, Accept: string}> */
    private const PROFILES = [
        [
            "User-Agent" => "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36",
            "Accept-Language" => "en-US,en;q=0.9",
            "Accept" => "text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8",
        ],
        [
            "User-Agent" => "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36",
            "Accept-Language" => "pt-BR,pt;q=0.9,en-US;q=0.8,en;q=0.7",
            "Accept" => "text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8",
        ],
        [
            "User-Agent" => "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36",
            "Accept-Language" => "en-GB,en;q=0.9",
            "Accept" => "text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8",
        ],
        [
            "User-Agent" => "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.2 Safari/605.1.15",
            "Accept-Language" => "en-US,en;q=0.9",
            "Accept" => "text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8",
        ],
        [
            "User-Agent" => "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36",
            "Accept-Language" => "pt-BR,pt;q=0.9,en;q=0.8",
            "Accept" => "text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8",
        ],
        [
            "User-Agent" => "Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:133.0) Gecko/20100101 Firefox/133.0",
            "Accept-Language" => "en-US,en;q=0.5",
            "Accept" => "text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,*/*;q=0.8",
        ],
        [
            "User-Agent" => "Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:133.0) Gecko/20100101 Firefox/133.0",
            "Accept-Language" => "es-ES,es;q=0.8,en;q=0.5",
            "Accept" => "text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,*/*;q=0.8",
        ],
        [
            "User-Agent" => "Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:133.0) Gecko/20100101 Firefox/133.0",
            "Accept-Language" => "fr-FR,fr;q=0.8,en-US;q=0.8,en;q=0.7",
            "Accept" => "text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,*/*;q=0.8",
        ],
        [
            "User-Agent" => "Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:133.0) Gecko/20100101 Firefox/133.0",
            "Accept-Language" => "de-DE,de;q=0.9,en;q=0.8",
            "Accept" => "text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,*/*;q=0.8",
        ],
        [
            "User-Agent" => "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36 Edg/131.0.0.0",
            "Accept-Language" => "it-IT,it;q=0.9,en;q=0.8",
            "Accept" => "text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8",
        ],
    ];

    public static function client(?ArticleSource $source = null): PendingRequest
    {
        $profile = self::PROFILES[array_rand(self::PROFILES)];

        $headers = [
            "User-Agent" => $profile["User-Agent"],
            "Accept-Language" => $profile["Accept-Language"],
            "Accept" => $profile["Accept"],
        ];

        if( $source?->user_agent ) {
            $headers["User-Agent"] = $source->user_agent;
        }

        if( $source?->accept_language ) {
            $headers["Accept-Language"] = $source->accept_language;
        }

        $request = Http::withHeaders($headers);

        if( $source?->proxy_server ) {
            $request = $request->withOptions([
                "proxy" => $source->proxy_server,
            ]);
        }

        return $request;
    }
}
