<?php

namespace App\Support;

use App\Models\AiPrompt;
use Illuminate\Support\Facades\Cache;

class AssistantPrompts
{
    public const PROVIDER_NAME = "Assistant (Ollama)";

    public const KEY_SELECT_SYSTEM = "select_system";

    public const KEY_STRUCTURE_SYSTEM = "structure_system";

    public static function get(string $key): ?AiPrompt
    {
        return Cache::remember(static::cacheKey($key), 3600, function () use ($key) {
            return AiPrompt::query()
                ->where("is_system", true)
                ->where("key", $key)
                ->first();
        });
    }

    public static function forget(string $key): void
    {
        Cache::forget(static::cacheKey($key));
    }

    public static function forgetAll(): void
    {
        static::forget(static::KEY_SELECT_SYSTEM);
        static::forget(static::KEY_STRUCTURE_SYSTEM);
    }

    private static function cacheKey(string $key): string
    {
        return "assistant_ai_prompt:{$key}";
    }
}
