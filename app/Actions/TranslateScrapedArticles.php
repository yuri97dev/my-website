<?php

namespace App\Actions;

use Illuminate\Support\Str;
use Symfony\AI\Platform\Bridge\DeepSeek\Factory as DeepSeekFactory;
use Symfony\AI\Platform\Bridge\Gemini\Factory as GeminiFactory;
use Symfony\AI\Platform\Message\Message;
use Symfony\AI\Platform\Message\MessageBag;
use League\HTMLToMarkdown\HtmlConverter;
use League\HTMLToMarkdown\Converter\TableConverter;
use League\HTMLToMarkdown\Converter\ConverterInterface;
use League\HTMLToMarkdown\ElementInterface;
use League\CommonMark\GithubFlavoredMarkdownConverter;
use App\Helpers\HtmlFormatter;
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

        $html_converter->getEnvironment()->addConverter(new class implements ConverterInterface {
            public function convert(ElementInterface $element): string
            {
                $tag = $element->getTagName();
                $inner = trim($element->getValue());

                return "\n\n<{$tag}>\n\n{$inner}\n\n</{$tag}>\n\n";
            }

            public function getSupportedTags(): array
            {
                return ["figure", "figcaption"];
            }
        });

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

        $translated_title = ltrim($translated_title, "#");
        $translated_title = trim($translated_title);

        $translated_content = $this->restoreLinksFromPlaceholders($translated_content);

        $translated_content = preg_replace(
            '#\s*(</?(?:figure|figcaption)\b[^>]*>)\s*#i',
            "\n\n$1\n\n",
            $translated_content,
        ) ?? $translated_content;

        $markdown_converter = new GithubFlavoredMarkdownConverter([
            "html_input" => "allow",
            "allow_unsafe_links" => false,
        ]);

        $translated_html = $markdown_converter->convert($translated_content);

        $translated_html = $this->restoreIframesFromPlaceholders($translated_html);

        $translated_html = HtmlFormatter::sanitize($translated_html);
        $translated_html = HtmlFormatter::beautify($translated_html);

        $article->translated_title = $translated_title;
        $article->translated_content = $translated_html;

        $article->save();
    }

    private function replaceLinksWithPlaceholders(string $markdown_content): string
    {
        $pieces = explode("](", $markdown_content);

        $final_content = [
            array_shift($pieces),
        ];

        $scraped_image_route_prefix = Str::beforeLast(
            route("blog.scraped-images.show", 0, false),
            "0",
        );

        foreach($pieces as $index => $piece) {
            $key = "LINK_{$index}";

            $sub_pieces = explode(")", $piece);

            $link = array_shift($sub_pieces);

            $valid_url = filter_var($link, FILTER_VALIDATE_URL) !== false;
            $is_internal_link = str_starts_with($link, $scraped_image_route_prefix);

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

    private function replaceIframesWithPlaceholders(string $content): string
    {
        $index = 0;

        return preg_replace_callback(
            '/<iframe\b[^>]*>.*?<\/iframe>/is',
            function(array $matches) use (&$index) {
                $key = "IFRAME_{$index}";
                $index++;

                $this->iframe_map[$key] = $matches[0];

                return "({$key})";
            },
            $content,
        ) ?? $content;
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
        foreach($this->iframe_map as $key => $tag) {
            $content = str_replace("({$key})", $tag, $content);
        }

        return $content;
    }
}
