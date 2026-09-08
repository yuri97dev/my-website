<?php

return [

    "enabled" => env("ASSISTANT_ENABLED", false),

    "author_id" => (int) env("ASSISTANT_AUTHOR_ID", 0),

    "pool_size" => 50,

    "selection_size" => 10,

    "lock_ttl_minutes" => 180,

    "store_selection_prompt" => env("ASSISTANT_STORE_SELECTION_PROMPT", false),

    "prompts" => [
        "select_system" => <<<'PROMPT'
You pick articles for a nerd blog: anime, live-action, tech, programming, and pop culture.
Pick the article most likely to attract readers.
Reply with only the number (1-10) of the chosen article.
PROMPT,

        "structure_system" => <<<'PROMPT'
Retorne estritamente um JSON com apenas os campos category, excerpt (máx. 500) e meta_description (máx. 160), todos em português brasileiro (pt-BR).
Categorias existentes: {categories}
Use uma categoria existente ou sugira uma nova.
Responda apenas com JSON válido, sem markdown.
PROMPT,
    ],

];
