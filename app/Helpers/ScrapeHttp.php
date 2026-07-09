<?php

namespace App\Helpers;

use App\Models\ArticleSource;
use App\Models\ScrapeHttpProfile;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

class ScrapeHttp
{
    private const RETRY_TRIES = 3;

    private const TIMEOUT = 30;

    private const CONNECT_TIMEOUT = 10;

    private static array $profileIndexes = [];

    private static ?array $profiles = null;

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

    private static function profiles(): array
    {
        if( self::$profiles === null ) {
            self::$profiles = ScrapeHttpProfile::query()
                ->enabled()
                ->pluck("headers")
                ->all();
        }

        if( self::$profiles === [] ) {
            throw new RuntimeException("No enabled scrape HTTP profiles found.");
        }

        return self::$profiles;
    }

    private static function profileFor(?ArticleSource $source): array
    {
        $key = $source?->id ?? 0;

        $profiles = self::profiles();

        if( !isset(self::$profileIndexes[$key]) ) {
            self::$profileIndexes[$key] = array_rand($profiles);
        }

        return $profiles[self::$profileIndexes[$key]];
    }

    private static function rotateProfileFor(?ArticleSource $source): array
    {
        $key = $source?->id ?? 0;

        $profiles = self::profiles();

        $index = self::$profileIndexes[$key] ?? array_rand($profiles);

        self::$profileIndexes[$key] = ($index + 1) % count($profiles);

        return $profiles[self::$profileIndexes[$key]];
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

        if( !($exception instanceof RequestException) ) {
            return false;
        }

        $status = $exception->response?->status();

        return $status === null
            || in_array($status, [403, 429], true)
            || $status >= 500;
    }

    private static function logRetry(Throwable $exception, ?ArticleSource $source): void
    {
        $context = $source ? " for source {$source->name}" : "";
        Log::warning("ScrapeHttp: request failed{$context}: {$exception->getMessage()}");
    }
}
