<?php

return [

    "enabled" => env("ASSISTANT_ENABLED", false),

    "author_id" => (int) env("ASSISTANT_AUTHOR_ID", 0),

    "lock_ttl_minutes" => 180,

];
