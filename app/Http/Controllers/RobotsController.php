<?php

namespace App\Http\Controllers;

class RobotsController extends Controller
{
    public function __invoke()
    {
        $base_url = rtrim(config("app.url"), "/");

        $content = implode("\n", [
            "User-agent: *",
            "Disallow: /admin",
            "Disallow: /account",
            "Disallow: /tools/sgc",
            "",
            "Sitemap: {$base_url}/sitemap.xml",
            "# llms.txt: {$base_url}/llms.txt",
        ]);

        return response($content, 200, [
            "Content-Type" => "text/plain; charset=utf-8",
        ]);
    }
}
