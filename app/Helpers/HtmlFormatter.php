<?php

namespace App\Helpers;

use Masterminds\HTML5;
use Symfony\Component\DomCrawler\UriResolver;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerAction;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;
use Symfony\Component\HtmlSanitizer\HtmlSanitizer;
use tidy;

class HtmlFormatter
{
    private const ALLOWED_IFRAME_DOMAINS = [
        "youtube.com",
        "www.youtube.com",
        "youtube-nocookie.com",
        "www.youtube-nocookie.com",
        "vimeo.com",
        "www.vimeo.com",
        "player.vimeo.com",
        "www.player.vimeo.com",
    ];

    public static function all(string $content, ?string $base_url = null): string
    {
        $content = self::resolveLazyLoadedImages($content);
        $content = self::sanitize($content);

        if( !empty($base_url) ) {
            $content = self::absolutizeUrls($content, $base_url);
        }

        return self::beautify($content);
    }

    public static function sanitize(string $content): string
    {
        $content = self::filterIframes($content);

        $config = (new HtmlSanitizerConfig())
            ->withMaxInputLength(-1)
            ->defaultAction(HtmlSanitizerAction::Block)
            ->allowRelativeLinks()
            ->allowRelativeMedias()
            ->allowElement("h1")
            ->allowElement("h2")
            ->allowElement("h3")
            ->allowElement("h4")
            ->allowElement("h5")
            ->allowElement("h6")
            ->allowElement("p")
            ->allowElement("br")
            ->allowElement("hr")
            ->allowElement("blockquote")
            ->allowElement("pre")
            ->allowElement("ul")
            ->allowElement("ol")
            ->allowElement("li")
            ->allowElement("strong")
            ->allowElement("em")
            ->allowElement("b")
            ->allowElement("i")
            ->allowElement("u")
            ->allowElement("s")
            ->allowElement("del")
            ->allowElement("ins")
            ->allowElement("mark")
            ->allowElement("sub")
            ->allowElement("sup")
            ->allowElement("small")
            ->allowElement("abbr", ["title"])
            ->allowElement("code")
            ->allowElement("a", ["href", "title"])
            ->allowElement("img", ["src", "alt", "title"])
            ->allowElement("figure")
            ->allowElement("figcaption")
            ->allowElement("table")
            ->allowElement("thead")
            ->allowElement("tbody")
            ->allowElement("tfoot")
            ->allowElement("tr")
            ->allowElement("th", ["colspan", "rowspan"])
            ->allowElement("td", ["colspan", "rowspan"])
            ->allowElement("caption")
            ->allowElement("iframe", ["src", "allowfullscreen", "frameborder", "title"])
            ->dropElement("script")
            ->dropElement("noscript")
            ->dropElement("style")
            ->dropElement("object")
            ->dropElement("embed")
            ->dropElement("form")
            ->dropElement("input")
            ->dropElement("textarea")
            ->dropElement("select")
            ->dropElement("button");

        $sanitizer = new HtmlSanitizer($config);

        return $sanitizer->sanitize($content);
    }

    public static function beautify(string $content): string
    {
        $tidy_config = [
            "indent" => true,
            "indent-spaces" => 4,
            "wrap" => 200,
            "output-xhtml" => false,
            "show-body-only" => true,
            "new-blocklevel-tags" => "figure,figcaption",
        ];

        $tidy = new tidy();

        $tidy->parseString($content, $tidy_config, "utf8");
        $tidy->cleanRepair();

        return (string) $tidy;
    }

    public static function resolveLazyLoadedImages(string $html): string
    {
        return self::transformHtmlFragment($html, function($node) {
            if(
                $node instanceof \DOMElement &&
                $node->tagName === "img" &&
                $node->hasAttribute("data-src")
            ) {
                $src = trim($node->getAttribute("data-src"));

                if( !empty($src) ) {
                    $node->setAttribute("src", $src);
                    $node->removeAttribute("data-src");
                }
            }
        });
    }

    public static function absolutizeUrls(string $html, string $base_url): string
    {
        $url_attributes = [ "href", "src" ];

        return self::transformHtmlFragment($html, function($node) use ($base_url, $url_attributes) {
            if( $node instanceof \DOMElement ) {
                foreach($url_attributes as $attribute) {
                    if( !$node->hasAttribute($attribute) ) continue;

                    $url = $node->getAttribute($attribute);

                    if( empty($url) ) continue;

                    $node->setAttribute($attribute, UriResolver::resolve($url, $base_url));
                }
            }
        });
    }

    public static function rewriteImageSources(string $html, callable $resolver): string
    {
        return self::transformHtmlFragment($html, function($node) use ($resolver) {
            if(
                $node instanceof \DOMElement &&
                $node->tagName === "img" &&
                $node->hasAttribute("src")
            ) {
                $src = trim($node->getAttribute("src"));

                if( !empty($src) ) {
                    $resolved = $resolver($src);

                    if( is_string($resolved) && $resolved !== "" ) {
                        $node->setAttribute("src", $resolved);
                    }
                }
            }
        });
    }

    private static function filterIframes(string $html): string
    {
        return self::transformHtmlFragment($html, function($node) {
            if(
                $node instanceof \DOMElement &&
                $node->tagName === "iframe"
            ) {
                $src = $node->getAttribute("src");
                $iframe_domain = parse_url($src, PHP_URL_HOST);

                if( !in_array($iframe_domain, self::ALLOWED_IFRAME_DOMAINS, true) ) {
                    $node->parentNode?->removeChild($node);
                    return;
                }

                while($node->firstChild) {
                    $node->removeChild($node->firstChild);
                }

                $src = strtok($src, "?") ?: $src;
                $node->setAttribute("src", $src);
            }
        });
    }

    private static function transformHtmlFragment(string $html, callable $visitor): string
    {
        if( empty($html) ) return $html;

        $html5 = new HTML5();
        $fragment = $html5->loadHTMLFragment($html);

        $walk = function($node) use (&$walk, $visitor) {
            $visitor($node);

            $children = [];

            foreach($node->childNodes as $child) {
                $children[] = $child;
            }

            foreach($children as $child) {
                $walk($child);
            }
        };

        foreach($fragment->childNodes as $child) {
            $walk($child);
        }

        return $html5->saveHTML($fragment);
    }
}
