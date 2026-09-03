<?php

namespace App\Actions;

use App\Models\Post;
use App\Models\PostCategory;
use Spatie\Sitemap\Sitemap;
use Spatie\Sitemap\Tags\Url;

class GenerateSitemap
{
    public function __invoke(): void
    {
        $this->build()->writeToFile(public_path("sitemap.xml"));
    }

    private function build(): Sitemap
    {
        $sitemap = Sitemap::create();

        $sitemap->add(
            Url::create(route("home"))
                ->setPriority(1.0)
        );

        $sitemap->add(
            Url::create(route("tools"))
                ->setPriority(0.7)
        );

        $sitemap->add(
            Url::create(route("tools.spreadsheet-formatter"))
                ->setPriority(0.7)
        );

        $sitemap->add(
            Url::create(route("terms_of_use"))
                ->setPriority(0.3)
        );

        $sitemap->add(
            Url::create(route("privacy_policy"))
                ->setPriority(0.3)
        );

        $sitemap->add(
            Url::create(route("blog.index"))
                ->setPriority(0.9)
        );

        PostCategory::query()
            ->whereHas("posts", fn ($query) => $query->published())
            ->orderBy("slug")
            ->get(["id", "slug"])
            ->each(function (PostCategory $category) use ($sitemap) {
                $sitemap->add(
                    Url::create(route("blog.index", $category->slug))
                        ->setPriority(0.8)
                );
            });

        Post::query()
            ->published()
            ->with("category:id,slug")
            ->select(["id", "slug", "category_id", "updated_at"])
            ->orderByDesc("published_at")
            ->cursor()
            ->each(function (Post $post) use ($sitemap) {
                $sitemap->add(
                    Url::create(route("blog.show", [
                        $post->category->slug,
                        $post->slug,
                    ]))
                        ->setLastModificationDate($post->updated_at)
                        ->setPriority(0.8)
                );
            });

        return $sitemap;
    }
}
