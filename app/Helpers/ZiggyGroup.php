<?php

namespace App\Helpers;

use Illuminate\Http\Request;

class ZiggyGroup
{
    public static function for(Request $request): ?string
    {
        return $request->user()?->isSystemAdmin() ? "full" : null;
    }
}
