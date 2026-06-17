<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class Ollama
{
    public static function embed(string $text): array
    {
        if( empty($text) ) return [];

        $url = config("services.ollama.base_url") . "embed";

        $response = Http::post($url, [
            "model" => config("services.ollama.embed_model"),
            "keep_alive" => config("services.ollama.keep_alive"),
            "input" => $text,
        ]);

        $response->throw();

        return $response->json();
    }
}
