<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class OllamaEmbeddingConfig
{
    public const KEY_EMBED_MODEL = "ollama.embed_model";

    public const KEY_EMBED_DIMENSION = "ollama.embed_dimension";

    public static function model(): string
    {
        $value = Cache::rememberForever(
            self::cacheKeyFor(self::KEY_EMBED_MODEL),
            fn () => Setting::getValue(self::KEY_EMBED_MODEL),
        );

        if( $value === null || $value === "" ) {
            throw new RuntimeException(
                "Modelo de embedding não configurado. Execute: php artisan db:seed --class=OllamaEmbeddingSettingSeeder",
            );
        }

        return $value;
    }

    public static function dimension(): int
    {
        $value = Cache::rememberForever(
            self::cacheKeyFor(self::KEY_EMBED_DIMENSION),
            fn () => Setting::getValue(self::KEY_EMBED_DIMENSION),
        );

        if( $value === null || $value === "" ) {
            throw new RuntimeException(
                "Dimensão de embedding não configurada. Execute: php artisan db:seed --class=OllamaEmbeddingSettingSeeder",
            );
        }

        return (int) $value;
    }

    public static function setModel(string $model): void
    {
        Setting::setValue(self::KEY_EMBED_MODEL, $model);
        DB::afterCommit(fn () => Cache::forget(self::cacheKeyFor(self::KEY_EMBED_MODEL)));
    }

    public static function setDimension(int $dimension): void
    {
        Setting::setValue(self::KEY_EMBED_DIMENSION, (string) $dimension);
        DB::afterCommit(fn () => Cache::forget(self::cacheKeyFor(self::KEY_EMBED_DIMENSION)));
    }

    public static function cacheKeyFor(string $key): string
    {
        return "setting." . $key;
    }
}
