<?php

namespace Database\Seeders;

use App\Models\ScrapeHttpProfile;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ScrapeHttpProfileSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the scrape HTTP browser profiles.
     */
    public function run(): void
    {
        $profiles = [
            [
                "name" => "Chrome Windows EN",
                "headers" => [
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
            ],
            [
                "name" => "Chrome Windows PT-BR",
                "headers" => [
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
            ],
            [
                "name" => "Chrome macOS EN-GB",
                "headers" => [
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
            ],
            [
                "name" => "Safari macOS EN",
                "headers" => [
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
            ],
            [
                "name" => "Chrome Linux PT-BR",
                "headers" => [
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
            ],
            [
                "name" => "Firefox Windows EN",
                "headers" => [
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
            ],
            [
                "name" => "Firefox Windows ES",
                "headers" => [
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
            ],
            [
                "name" => "Firefox macOS FR",
                "headers" => [
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
            ],
            [
                "name" => "Firefox Ubuntu DE",
                "headers" => [
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
            ],
            [
                "name" => "Edge Windows IT",
                "headers" => [
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
            ],
        ];

        foreach( $profiles as $profile ) {
            ScrapeHttpProfile::query()->updateOrCreate(
                ["name" => $profile["name"]],
                ["headers" => $profile["headers"]],
            );
        }
    }
}
