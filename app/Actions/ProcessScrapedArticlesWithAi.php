<?php

namespace App\Actions;

use Symfony\AI\Platform\Bridge\DeepSeek\Factory as DeepSeekFactory;
use Symfony\AI\Platform\Bridge\Gemini\Factory as GeminiFactory;
use Symfony\AI\Platform\Bridge\OpenAi\Factory as OpenAiFactory;
use Symfony\AI\Platform\Message\Message;
use Symfony\AI\Platform\Message\MessageBag;
use Symfony\AI\Platform\PlatformInterface;
use App\Models\AiPrompt;
use App\Models\AiProvider;
use App\Models\ScrapedArticle;
use Exception;

class ProcessScrapedArticlesWithAi
{
    public function __invoke(): void
    {
        $article = ScrapedArticle::query()
            ->enabled()
            ->whereNull("translation_rejected_at")
            ->whereNull("translated_content")
            ->whereHas("source.aiPrompts")
            ->with("source.aiPrompts.aiProvider")
            ->orderBy("id")
            ->first();

        if( !$article ) {
            return;
        }

        $prompts = $article->source->aiPrompts;

        if( $prompts->contains(fn ($prompt) => !$prompt->aiProvider->is_enabled) ) {
            return;
        }

        $content = "<h1>{$article->title}</h1>\n\n{$article->content}";

        foreach($prompts as $prompt) {
            $content = $this->invokePrompt($prompt, $content);
            sleep(rand(1, 3));
        }

        [$translated_title, $translated_content] = explode("</h1>", $content, 2);

        $translated_title = ltrim($translated_title, "<h1>");
        $translated_title = trim($translated_title);

        $article->translated_title = $translated_title;
        $article->translated_content = $translated_content;

        $article->save();
    }

    private function invokePrompt(AiPrompt $prompt, string $content): string
    {
        $platform = $this->platform($prompt->aiProvider);

        $messages = new MessageBag(
            Message::forSystem($prompt->content),
            Message::ofUser($content)
        );

        $result = $platform->invoke($prompt->model, $messages);

        return $result->asText();
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
