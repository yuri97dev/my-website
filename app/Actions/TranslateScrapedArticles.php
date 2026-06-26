<?php

namespace App\Actions;

use Symfony\AI\Platform\Bridge\DeepSeek\Factory as DeepSeekFactory;
use Symfony\AI\Platform\Bridge\Gemini\Factory as GeminiFactory;
use Symfony\AI\Platform\Message\Message;
use Symfony\AI\Platform\Message\MessageBag;
use League\HTMLToMarkdown\HtmlConverter;
use League\HTMLToMarkdown\Converter\TableConverter;
use League\CommonMark\GithubFlavoredMarkdownConverter;
use App\Models\AiPrompt;
use App\Models\ScrapedArticle;
use Exception;

class TranslateScrapedArticles
{
    private array $link_map = [];
    private array $iframe_map = [];

    public function __invoke(): void
    {
        $ai_prompt = AiPrompt::query()
            ->key("post-translation")
            ->with("aiProvider")
            ->first();

        if( !$ai_prompt || !$ai_prompt->aiProvider->is_enabled ) {
            return;
        }

        $api_key = $ai_prompt->aiProvider->api_key;
        $provider = $ai_prompt->aiProvider->provider;

        $platform = match($provider) {
            "gemini" => GeminiFactory::createPlatform($api_key),
            "deepseek" => DeepSeekFactory::createPlatform($api_key),
            default => throw new Exception("Provedor não suportado"),
        };

        $article = ScrapedArticle::query()
            ->enabled()
            ->whereNull("translated_content")
            ->orderBy("id")
            ->first();

        if( !$article ) {
            return;
        }

        $content = $article->content;

        $html_converter = new HtmlConverter();
        $html_converter->getEnvironment()->addConverter(new TableConverter());

        $markdown_content = $html_converter->convert($content);

        $markdown_with_placeholders = $this->replaceLinksWithPlaceholders($markdown_content);
        $markdown_with_placeholders = $this->replaceIframesWithPlaceholders($markdown_with_placeholders);

        $content_to_translate = implode("\n\n", [
            "# {$article->title}",
            $markdown_with_placeholders,
        ]);

        $messages = new MessageBag(
            Message::forSystem($ai_prompt->content),
            Message::ofUser($content_to_translate)
        );

        $result = $platform->invoke($ai_prompt->model, $messages);

        $translated_content = $result->asText();

        [$translated_title, $translated_content] = explode("\n\n", $translated_content, 2);

        $translated_content = $this->restoreLinksFromPlaceholders($translated_content);
        $translated_content = $this->restoreIframesFromPlaceholders($translated_content);

        $markdown_converter = new GithubFlavoredMarkdownConverter([
            "html_input" => "strip",
            "allow_unsafe_links" => false,
        ]);

        $article->translated_title = $translated_title;
        $article->translated_content = $markdown_converter->convert($translated_content);

        $article->save();
    }

    private function replaceLinksWithPlaceholders(string $markdown_content): string
    {
        $pieces = explode("](", $markdown_content);

        $final_content = [
            array_shift($pieces),
        ];

        foreach($pieces as $index => $piece) {
            $key = "LINK_{$index}";

            $sub_pieces = explode(")", $piece);

            $link = array_shift($sub_pieces);

            $valid_url = filter_var($link, FILTER_VALIDATE_URL) !== false;
            $is_internal_link = str_starts_with($link, "/blog/scraped-images");

            if( !$valid_url && !$is_internal_link ) {
                $final_content[] = $piece;
                continue;
            }

            $rest = implode(")", $sub_pieces);

            $final_content[] = "{$key}){$rest}";

            $this->link_map[$key] = $link;
        }

        return implode("](", $final_content);
    }

    private function replaceIframesWithPlaceholders(string $markdown_content): string
    {
        $pieces = explode("<iframe", $markdown_content);

        $final_content = [
            array_shift($pieces),
        ];

        foreach($pieces as $index => $piece) {
            $key = "IFRAME_{$index}";

            $sub_pieces = explode("</iframe>", $piece);

            $iframe_props = array_shift($sub_pieces);
            $iframe_props = str_replace("'", '"', $iframe_props);

            $src_pieces = explode("src=", $iframe_props);

            $link = explode('"', $src_pieces[1])[1];

            if( filter_var($link, FILTER_VALIDATE_URL) === false ) {
                $final_content[] = $piece;
                continue;
            }

            $rest = implode("", $sub_pieces);

            $final_content[] = "({$key}) {$rest}";

            $this->iframe_map[$key] = $link;
        }

        return implode("", $final_content);
    }

    private function restoreLinksFromPlaceholders(string $content): string
    {
        foreach($this->link_map as $key => $link) {
            $content = str_replace("({$key})", "($link)", $content);
        }

        return $content;
    }

    private function restoreIframesFromPlaceholders(string $content): string
    {
        foreach($this->iframe_map as $key => $link) {
            $content = str_replace(
                "({$key})",
                "<iframe allowfullscreen frameborder=\"0\" src=\"{$link}\"></iframe>",
                $content
            );
        }

        return $content;
    }
}
