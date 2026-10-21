<?php

namespace Database\Seeders;

use App\Models\Setting;
use App\Support\OllamaEmbeddingConfig;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;

class OllamaEmbeddingSettingSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Valores padrão de embedding (antes em OLLAMA_EMBED_MODEL / OLLAMA_EMBED_DIMENSION).
     */
    public function run(): void
    {
        Setting::query()->firstOrCreate(
            ["key" => OllamaEmbeddingConfig::KEY_EMBED_MODEL],
            ["value" => "bge-m3"],
        );

        Setting::query()->firstOrCreate(
            ["key" => OllamaEmbeddingConfig::KEY_EMBED_DIMENSION],
            ["value" => "1024"],
        );

        Cache::forget(OllamaEmbeddingConfig::cacheKeyFor(OllamaEmbeddingConfig::KEY_EMBED_MODEL));
        Cache::forget(OllamaEmbeddingConfig::cacheKeyFor(OllamaEmbeddingConfig::KEY_EMBED_DIMENSION));
    }
}
