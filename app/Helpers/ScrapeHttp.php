<?php

namespace App\Helpers;

use App\Models\ArticleSource;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Throwable;

class ScrapeHttp
{
    private const RETRY_TRIES = 3;

    private const TIMEOUT = 30;

    private const CONNECT_TIMEOUT = 10;

    private static array $profileIndexes = [];

    private const PROFILES = [
        [
            "User-Agent" => "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36",
            "Accept" => "text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8",
            "Accept-Language" => "en-US,en;q=0.9",
            "Accept-Encoding" => "gzip, deflate, br",
            "Connection" => "keep-alive",
            "Upgrade-Insecure-Requests" => "1",
            "Sec-Ch-Ua" => '"Google Chrome";v="131", "Chromium";v="131", "Not_A Brand";v="24"',
            "Sec-Ch-Ua-Mobile" => "?0",
            "Sec-Ch-Ua-Platform" => '"Windows"',
            "Sec-Fetch-Site" => "none",
            "Sec-Fetch-Mode" => "navigate",
            "Sec-Fetch-User" => "?1",
            "Sec-Fetch-Dest" => "document",
        ],
        [
            "User-Agent" => "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36",
            "Accept" => "text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8",
            "Accept-Language" => "pt-BR,pt;q=0.9,en-US;q=0.8,en;q=0.7",
            "Accept-Encoding" => "gzip, deflate, br",
            "Connection" => "keep-alive",
            "Upgrade-Insecure-Requests" => "1",
            "Sec-Ch-Ua" => '"Google Chrome";v="131", "Chromium";v="131", "Not_A Brand";v="24"',
            "Sec-Ch-Ua-Mobile" => "?0",
            "Sec-Ch-Ua-Platform" => '"Windows"',
            "Sec-Fetch-Site" => "none",
            "Sec-Fetch-Mode" => "navigate",
            "Sec-Fetch-User" => "?1",
            "Sec-Fetch-Dest" => "document",
        ],
        [
            "User-Agent" => "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36",
            "Accept" => "text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8",
            "Accept-Language" => "en-GB,en;q=0.9",
            "Accept-Encoding" => "gzip, deflate, br",
            "Connection" => "keep-alive",
            "Upgrade-Insecure-Requests" => "1",
            "Sec-Ch-Ua" => '"Google Chrome";v="131", "Chromium";v="131", "Not_A Brand";v="24"',
            "Sec-Ch-Ua-Mobile" => "?0",
            "Sec-Ch-Ua-Platform" => '"macOS"',
            "Sec-Fetch-Site" => "none",
            "Sec-Fetch-Mode" => "navigate",
            "Sec-Fetch-User" => "?1",
            "Sec-Fetch-Dest" => "document",
        ],
        [
            "User-Agent" => "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.2 Safari/605.1.15",
            "Accept" => "text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8",
            "Accept-Language" => "en-US,en;q=0.9",
            "Accept-Encoding" => "gzip, deflate, br",
            "Connection" => "keep-alive",
            "Upgrade-Insecure-Requests" => "1",
            "Sec-Fetch-Site" => "none",
            "Sec-Fetch-Mode" => "navigate",
            "Sec-Fetch-Dest" => "document",
        ],
        [
            "User-Agent" => "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36",
            "Accept" => "text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8",
            "Accept-Language" => "pt-BR,pt;q=0.9,en;q=0.8",
            "Accept-Encoding" => "gzip, deflate, br",
            "Connection" => "keep-alive",
            "Upgrade-Insecure-Requests" => "1",
            "Sec-Ch-Ua" => '"Google Chrome";v="131", "Chromium";v="131", "Not_A Brand";v="24"',
            "Sec-Ch-Ua-Mobile" => "?0",
            "Sec-Ch-Ua-Platform" => '"Linux"',
            "Sec-Fetch-Site" => "none",
            "Sec-Fetch-Mode" => "navigate",
            "Sec-Fetch-User" => "?1",
            "Sec-Fetch-Dest" => "document",
        ],
        [
            "User-Agent" => "Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:133.0) Gecko/20100101 Firefox/133.0",
            "Accept" => "text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,*/*;q=0.8",
            "Accept-Language" => "en-US,en;q=0.5",
            "Accept-Encoding" => "gzip, deflate, br",
            "Connection" => "keep-alive",
            "Upgrade-Insecure-Requests" => "1",
            "DNT" => "1",
            "TE" => "trailers",
            "Sec-Fetch-Site" => "none",
            "Sec-Fetch-Mode" => "navigate",
            "Sec-Fetch-User" => "?1",
            "Sec-Fetch-Dest" => "document",
        ],
        [
            "User-Agent" => "Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:133.0) Gecko/20100101 Firefox/133.0",
            "Accept" => "text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,*/*;q=0.8",
            "Accept-Language" => "es-ES,es;q=0.8,en;q=0.5",
            "Accept-Encoding" => "gzip, deflate, br",
            "Connection" => "keep-alive",
            "Upgrade-Insecure-Requests" => "1",
            "DNT" => "1",
            "TE" => "trailers",
            "Sec-Fetch-Site" => "none",
            "Sec-Fetch-Mode" => "navigate",
            "Sec-Fetch-User" => "?1",
            "Sec-Fetch-Dest" => "document",
        ],
        [
            "User-Agent" => "Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:133.0) Gecko/20100101 Firefox/133.0",
            "Accept" => "text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,*/*;q=0.8",
            "Accept-Language" => "fr-FR,fr;q=0.8,en-US;q=0.8,en;q=0.7",
            "Accept-Encoding" => "gzip, deflate, br",
            "Connection" => "keep-alive",
            "Upgrade-Insecure-Requests" => "1",
            "DNT" => "1",
            "TE" => "trailers",
            "Sec-Fetch-Site" => "none",
            "Sec-Fetch-Mode" => "navigate",
            "Sec-Fetch-User" => "?1",
            "Sec-Fetch-Dest" => "document",
        ],
        [
            "User-Agent" => "Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:133.0) Gecko/20100101 Firefox/133.0",
            "Accept" => "text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,*/*;q=0.8",
            "Accept-Language" => "de-DE,de;q=0.9,en;q=0.8",
            "Accept-Encoding" => "gzip, deflate, br",
            "Connection" => "keep-alive",
            "Upgrade-Insecure-Requests" => "1",
            "DNT" => "1",
            "TE" => "trailers",
            "Sec-Fetch-Site" => "none",
            "Sec-Fetch-Mode" => "navigate",
            "Sec-Fetch-User" => "?1",
            "Sec-Fetch-Dest" => "document",
        ],
        [
            "User-Agent" => "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36 Edg/131.0.0.0",
            "Accept" => "text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8",
            "Accept-Language" => "it-IT,it;q=0.9,en;q=0.8",
            "Accept-Encoding" => "gzip, deflate, br",
            "Connection" => "keep-alive",
            "Upgrade-Insecure-Requests" => "1",
            "Sec-Ch-Ua" => '"Microsoft Edge";v="131", "Chromium";v="131", "Not_A Brand";v="24"',
            "Sec-Ch-Ua-Mobile" => "?0",
            "Sec-Ch-Ua-Platform" => '"Windows"',
            "Sec-Fetch-Site" => "none",
            "Sec-Fetch-Mode" => "navigate",
            "Sec-Fetch-User" => "?1",
            "Sec-Fetch-Dest" => "document",
        ],
    ];

    public static function client(?ArticleSource $source = null): PendingRequest
    {
        $profile = self::profileFor($source);

        $request = Http::withHeaders(self::buildHeaders($profile, $source))
            ->timeout(self::TIMEOUT)
            ->connectTimeout(self::CONNECT_TIMEOUT)
            ->retry(
                self::RETRY_TRIES,
                function (int $attempt, Throwable $exception): int {
                    $base = min(1000 * (2 ** ($attempt - 1)), 8000);
                    return $base + random_int(0, 500);
                },
                function (Throwable $exception, PendingRequest $request) use ($source): bool {
                    if( !self::shouldRetry($exception) ) {
                        return false;
                    }

                    self::logRetry($exception, $source);

                    $request->withHeaders(
                        self::buildHeaders(self::rotateProfileFor($source), $source),
                    );

                    return true;
                },
                throw: false,
            );

        if( $source?->proxy_server ) {
            $request = $request->withOptions([
                "proxy" => $source->proxy_server,
            ]);
        }

        return $request;
    }

    private static function profileFor(?ArticleSource $source): array
    {
        $key = self::profileCacheKey($source);

        if( !isset(self::$profileIndexes[$key]) ) {
            self::$profileIndexes[$key] = array_rand(self::PROFILES);
        }

        return self::PROFILES[self::$profileIndexes[$key]];
    }

    private static function rotateProfileFor(?ArticleSource $source): array
    {
        $key = self::profileCacheKey($source);
        $index = self::$profileIndexes[$key] ?? array_rand(self::PROFILES);

        self::$profileIndexes[$key] = ($index + 1) % count(self::PROFILES);

        return self::PROFILES[self::$profileIndexes[$key]];
    }

    private static function profileCacheKey(?ArticleSource $source): int|string
    {
        return $source?->id ?? 0;
    }

    private static function buildHeaders(array $profile, ?ArticleSource $source): array
    {
        $headers = $profile;

        if( $source?->user_agent ) {
            $headers["User-Agent"] = $source->user_agent;
        }

        if( $source?->accept_language ) {
            $headers["Accept-Language"] = $source->accept_language;
        }

        $referer = self::refererFor($source);

        if( $referer ) {
            $headers["Referer"] = $referer;
        }

        return $headers;
    }

    private static function refererFor(?ArticleSource $source): ?string
    {
        if( !$source?->list_url ) {
            return null;
        }

        $parts = parse_url($source->list_url);

        if( !isset($parts["scheme"], $parts["host"]) ) {
            return null;
        }

        return $parts["scheme"] . "://" . $parts["host"] . "/";
    }

    private static function shouldRetry(Throwable $exception): bool
    {
        if( $exception instanceof ConnectionException ) {
            return true;
        }

        if( $exception instanceof RequestException ) {
            $status = $exception->response?->status();

            if( $status === null ) {
                return true;
            }

            if( in_array($status, [403, 429], true) ) {
                return true;
            }

            if( $status >= 500 ) {
                return true;
            }

            return false;
        }

        return false;
    }

    private static function logRetry(Throwable $exception, ?ArticleSource $source): void
    {
        $context = $source ? " for source {$source->name}" : "";
        Log::warning("ScrapeHttp: request failed{$context}: {$exception->getMessage()}");
    }
}
