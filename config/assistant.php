<?php

return [

    "enabled" => env("ASSISTANT_ENABLED", false),

    "author_id" => (int) env("ASSISTANT_AUTHOR_ID", 0),

    "pool_size" => 50,

    "selection_size" => 10,

    "lock_ttl_minutes" => 180,

    "store_selection_prompt" => env("ASSISTANT_STORE_SELECTION_PROMPT", false),

];
