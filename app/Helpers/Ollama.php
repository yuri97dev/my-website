<?php

namespace App\Helpers;

use App\Support\OllamaEmbeddingConfig;
use Illuminate\Support\Facades\Http;

class Ollama
{
    private static function apiUrl(string $path): string
    {
        $url = config("services.ollama.base_url");
        $url = rtrim($url, "/");

        return $url . "/" . ltrim($path, "/");
    }

    public static function tags(): array
    {
        $response = Http::timeout((int) config("services.ollama.timeout", 120))
            ->get(self::apiUrl("tags"));

        $response->throw();

        return $response->json();
    }

    public static function pull(string $model): array
    {
        $response = Http::timeout((int) config("services.ollama.pull_timeout", 3600))
            ->post(self::apiUrl("pull"), [
                "name" => $model,
                "stream" => false,
            ]);

        $response->throw();

        return $response->json();
    }

    public static function embed(string $text, ?string $model = null): array
    {
        if( empty($text) ) return [];

        $response = Http::timeout((int) config("services.ollama.timeout", 120))
            ->post(self::apiUrl("embed"), [
                "model" => $model ?? OllamaEmbeddingConfig::model(),
                "keep_alive" => config("services.ollama.keep_alive"),
                "input" => $text,
            ]);

        $response->throw();

        return $response->json();
    }

    public static function generate(string $prompt, array $params = []): array
    {
        $payload = array_merge([
            "model" => config("services.ollama.chat_model"),
            "keep_alive" => config("services.ollama.keep_alive"),
            "prompt" => $prompt,
            "stream" => false,
            "think" => false,
        ], $params);

        $response = Http::timeout((int) config("services.ollama.timeout", 120))
            ->post(self::apiUrl("generate"), $payload);

        $response->throw();

        return $response->json();
    }

    public static function chat(string $system, string $user, array $params = []): string
    {
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
            ->post(self::apiUrl("chat"), $payload);

        $response->throw();

        return (string) $response->json("message.content");
    }
}
