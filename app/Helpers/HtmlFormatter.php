<?php

namespace App\Helpers;

use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;
use Symfony\Component\HtmlSanitizer\HtmlSanitizer;
use League\HTMLToMarkdown\HtmlConverter;
use League\HTMLToMarkdown\Converter\TableConverter;
use League\CommonMark\GithubFlavoredMarkdownConverter;
use tidy;

class HtmlFormatter
{
    public static function all(string $content): string
    {
        $sanitized_content = self::sanitize($content);
        $markdown_content = self::convertToMarkdown($sanitized_content);
        $html_content = self::convertFromMarkdown($markdown_content);
        $beautified_content = self::beautify($html_content);

        return $beautified_content;
    }

    public static function sanitize(string $content): string
    {
        $config = (new HtmlSanitizerConfig())
            ->withMaxInputLength(-1)
            ->allowStaticElements()
            ->allowRelativeLinks()
            ->allowRelativeMedias()
            ->dropElement("script")
            ->dropElement("noscript")
            ->dropElement("iframe")
            ->dropElement("object")
            ->dropElement("embed")
            ->dropElement("form");

        $sanitizer = new HtmlSanitizer($config);

        return $sanitizer->sanitize($content);
    }

    public static function convertToMarkdown(string $content): string
    {
        $converter = new HtmlConverter([ "strip_tags" => true ]);
        $converter->getEnvironment()->addConverter(new TableConverter());

        return $converter->convert($content);
    }

    public static function convertFromMarkdown(string $content): string
    {
        $converter = new GithubFlavoredMarkdownConverter([
            "html_input" => "strip",
            "allow_unsafe_links" => false,
        ]);

        return $converter->convert($content);
    }

    public static function beautify(string $content): string
    {
        $tidy_config = [
            "indent" => true,
            "indent-spaces" => 4,
            "wrap" => 200,
            "output-xhtml" => false,
            "show-body-only" => true,
        ];

        $tidy = new tidy();

        $tidy->parseString($content, $tidy_config, "utf8");
        $tidy->cleanRepair();

        return (string) $tidy;
    }
}
