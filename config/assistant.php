<?php

return [

    "enabled" => env("ASSISTANT_ENABLED", false),

    "author_id" => (int) env("ASSISTANT_AUTHOR_ID", 0),

    "pool_size" => 50,

    "selection_size" => 10,

    "lock_ttl_minutes" => 180,

    "prompts" => [
        "select_system" => <<<'PROMPT'
You pick articles for a nerd blog: anime, live-action, tech, programming, and pop culture.
Pick the article most likely to attract readers.
Reply with only the number (1-10) of the chosen article.
PROMPT,

        "structure_system" => <<<'PROMPT'
Structure the translated article as JSON for a blog post.
Required fields: title (max 200), slug (kebab-case), category (name), excerpt (max 500), content (HTML), meta_description (max 160).
Existing categories: {categories}
Use an existing category or suggest a new one.
Reply with valid JSON only, no markdown.
PROMPT,
    ],

];
