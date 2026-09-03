<?php

namespace App\Actions;

use App\Models\Post;
use App\Models\PostCategory;

class GenerateLlmsTxt
{
    public function __invoke(): void
    {
        file_put_contents(public_path("llms.txt"), $this->build());
    }

    private function build(): string
    {
        $base_url = rtrim(config("app.url"), "/");
        $site_name = config("app.name");
        $config = config("llms");

        $lines = [
            "# {$site_name}",
            "",
            "> {$config["summary"]}",
            "",
            $config["preamble"],
            "",
            "## Páginas",
            "",
            $this->link(
                route("home"),
                $config["pages"]["home"]["title"],
                $config["pages"]["home"]["description"],
            ),
            "",
            "## Blog",
            "",
            $this->link(
                route("blog.index"),
                $config["pages"]["blog"]["title"],
                $config["pages"]["blog"]["description"],
            ),
        ];

        $categories = PostCategory::query()
            ->whereHas("posts", fn ($query) => $query->published())
            ->orderBy("slug")
            ->get(["slug", "name"]);

        if( $categories->isNotEmpty() ) {
            $lines[] = "";

            foreach( $categories as $category ) {
                $lines[] = $this->link(
                    route("blog.index", $category->slug),
                    $category->name,
                    "Artigos na categoria {$category->name}.",
                );
            }
        }

        $posts = Post::query()
            ->published()
            ->with("category:id,slug")
            ->select(["id", "slug", "title", "excerpt", "meta_description", "category_id", "published_at"])
            ->orderByDesc("published_at")
            ->limit($config["max_posts"])
            ->get();

        if( $posts->isNotEmpty() ) {
            $lines[] = "";

            foreach( $posts as $post ) {
                $description = $post->meta_description ?: $post->excerpt;

                $lines[] = $this->link(
                    route("blog.show", [$post->category->slug, $post->slug]),
                    $post->title,
                    $description ?: "Artigo publicado em {$post->published_at->format("d/m/Y")}.",
                );
            }
        }

        $meta = config("meta");

        $lines = array_merge($lines, [
            "",
            "## Ferramentas",
            "",
            $this->link(
                route("tools"),
                $config["pages"]["tools"]["title"],
                $config["pages"]["tools"]["description"],
            ),
            $this->link(
                route("tools.spreadsheet-formatter"),
                $meta["tools.spreadsheet-formatter"]["title"],
                $meta["tools.spreadsheet-formatter"]["description"],
            ),
            "",
            "## Legal",
            "",
            $this->link(
                route("terms_of_use"),
                $config["pages"]["terms_of_use"]["title"],
                $config["pages"]["terms_of_use"]["description"],
            ),
            $this->link(
                route("privacy_policy"),
                $config["pages"]["privacy_policy"]["title"],
                $config["pages"]["privacy_policy"]["description"],
            ),
            "",
            "## Descoberta",
            "",
            $this->link(
                "{$base_url}/sitemap.xml",
                "Sitemap",
                "Lista completa de URLs públicas indexáveis do site.",
            ),
        ]);

        return implode("\n", $lines)."\n";
    }

    private function link(string $url, string $title, string $description): string
    {
        $title = str_replace(["[", "]"], ["\\[", "\\]"], $title);
        $description = str_replace("\n", " ", trim($description));

        return "- [{$title}]({$url}): {$description}";
    }
}
