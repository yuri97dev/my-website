<?php

namespace App\Http\Controllers;

class RobotsController extends Controller
{
    public function __invoke()
    {
        $sitemap_url = rtrim(config("app.url"), "/")."/sitemap.xml";

        $content = implode("\n", [
            "User-agent: *",
            "Disallow: /admin",
            "Disallow: /account",
            "Disallow: /tools/sgc",
            "",
            "Sitemap: {$sitemap_url}",
        ]);

        return response($content, 200, [
            "Content-Type" => "text/plain",
        ]);
    }
}
