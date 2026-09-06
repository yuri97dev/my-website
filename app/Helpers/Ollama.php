<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class Ollama
{
    public static function embed(string $text): array
    {
        if( empty($text) ) return [];

        $url = config("services.ollama.base_url");
        $url = rtrim($url, "/") . "/embed";

        $response = Http::post($url, [
            "model" => config("services.ollama.embed_model"),
            "keep_alive" => config("services.ollama.keep_alive"),
            "input" => $text,
        ]);

        $response->throw();

        return $response->json();
    }

    public static function generate(string $prompt, array $params = []): array
    {
        $url = config("services.ollama.base_url");
        $url = rtrim($url, "/") . "/generate";

        $payload = array_merge([
            "model" => config("services.ollama.chat_model"),
            "keep_alive" => config("services.ollama.keep_alive"),
            "prompt" => $prompt,
            "stream" => false,
            "think" => false,
        ], $params);

        $response = Http::timeout((int) config("services.ollama.timeout", 120))
            ->post($url, $payload);

        $response->throw();

        return $response->json();
    }

    public static function chat(string $system, string $user, array $params = []): string
    {
        $url = config("services.ollama.base_url");
        $url = rtrim($url, "/") . "/chat";

        $payload = array_merge([
            "model" => config("services.ollama.chat_model"),
            "keep_alive" => config("services.ollama.keep_alive"),
            "stream" => false,
            "think" => false,
            "messages" => [
                ["role" => "system", "content" => $system],
                ["role" => "user", "content" => $user],
            ],
        ], $params);

        $response = Http::timeout((int) config("services.ollama.timeout", 120))
            ->post($url, $payload);

        $response->throw();

        return (string) $response->json("message.content");
    }
}
