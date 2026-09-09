<?php

return [
    "groups" => [
        "full" => ["*"],
    ],

    "except" => [
        "admin.*",
        "api.sessions.*",
        "api.users.*",
        "api.jobs.*",
        "api.logs.*",
        "api.article-sources.*",
        "api.ai-providers.*",
        "api.ai-prompts.*",
        "api.ai-prompt-templates.*",
        "api.scraped-articles.*",
        "api.scrape-http-profiles.*",
        "api.blog.posts.get-by-id",
        "api.blog.posts.store",
        "api.blog.posts.update",
        "api.blog.posts.destroy",
        "api.blog.posts.images.*",
        "api.blog.posts.views.index",
        "api.blog.posts.categories.store",
        "api.blog.posts.categories.update",
        "api.blog.posts.categories.destroy",
        "api.blog.posts.tags.store",
        "api.blog.posts.tags.update",
        "api.blog.posts.tags.destroy",
    ],
];
