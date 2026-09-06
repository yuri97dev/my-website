<?php

namespace App\Actions;

use Symfony\AI\Platform\Bridge\DeepSeek\Factory as DeepSeekFactory;
use Symfony\AI\Platform\Bridge\Gemini\Factory as GeminiFactory;
use Symfony\AI\Platform\Bridge\OpenAi\Factory as OpenAiFactory;
use Symfony\AI\Platform\Message\Message;
use Symfony\AI\Platform\Message\MessageBag;
use Symfony\AI\Platform\PlatformInterface;
use App\Helpers\HtmlFormatter;
use App\Models\AiPrompt;
use App\Models\AiProvider;
use App\Models\ScrapedArticle;
use App\Models\ScrapedArticleTranslationCache;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Exception;
use Throwable;

class TranslateScrapedArticle
{
    public const MAX_TRANSLATION_ATTEMPTS = 3;

    public function __invoke(ScrapedArticle $article): void
    {
        $article->load([
            "source.aiPrompts.aiProvider",
            "translationCaches",
        ]);

        $prompts = $article->source->aiPrompts;
        $caches = $article->translationCaches->keyBy("ai_prompt_id");

        $content = implode("\n\n", [
            "<h1>{$article->title}</h1>",
            $article->content,
            "A matéria acima foi extraída de: {$article->source->url}",
        ]);

        try {
            foreach($prompts as $prompt) {
                $cached = $caches->get($prompt->id);

                if( $cached ) {
                    $content = $this->stripCodeFences($cached->content);
                    continue;
                }

                $content = $this->invokePrompt($prompt, $content);

                ScrapedArticleTranslationCache::query()->updateOrCreate(
                    [
                        "scraped_article_id" => $article->id,
                        "ai_prompt_id" => $prompt->id,
                    ],
                    [
                        "content" => $content,
                    ],
                );

                sleep(rand(1, 3));
            }

            [$translated_title, $translated_content] = HtmlFormatter::splitHeading($content);

            if( $translated_title === "" || mb_strlen($translated_title) > 500 ) {
                $translated_title = $article->title;
            }

            $article->translated_title = $translated_title;
            $article->translated_content = $translated_content;

            if( $article->processing_by !== null && !$article->isProcessedByAssistant() ) {
                $article->processing_by = null;
                $article->processing_at = null;
            }

            $article->save();

            $article->translationCaches()->delete();

            if( $article->isProcessedByAssistant() ) {
                (new ProcessScrapedArticlesWithAi)->structureDraftForArticle($article->fresh());
            }
        } catch(Throwable $e) {
            Log::error("TranslateScrapedArticle: failed to translate article {$article->id}: {$e->getMessage()}");

            $article->increment("translation_attempts");
            $article->refresh();

            if( $article->translation_attempts >= self::MAX_TRANSLATION_ATTEMPTS ) {
                ProcessScrapedArticlesWithAi::releaseAssistantLock($article);
            }
        }
    }

    private function invokePrompt(AiPrompt $prompt, string $content): string
    {
        $platform = $this->platform($prompt->aiProvider);

        $messages = new MessageBag(
            Message::forSystem($prompt->content),
            Message::ofUser($content)
        );

        $result = $platform->invoke($prompt->model, $messages);

        return $this->stripCodeFences($result->asText());
    }

    private function stripCodeFences(string $content): string
    {
        $content = trim($content);

        if( !str_starts_with($content, "```") ) {
            return $content;
        }

        $line_break = strpos($content, "\n");

        $content = $line_break === false ? "" : substr($content, $line_break + 1);
        $content = rtrim($content);

        if( str_ends_with($content, "```") ) {
            $content = substr($content, 0, -3);
        }

        return trim($content);
    }

    private function platform(AiProvider $provider): PlatformInterface
    {
        $api_key = $provider->api_key;

        return match($provider->provider) {
            "gemini" => GeminiFactory::createPlatform($api_key),
            "deepseek" => DeepSeekFactory::createPlatform($api_key),
            "openai" => OpenAiFactory::createPlatform($api_key),
            default => throw new Exception("Provedor não suportado"),
        };
    }
}
