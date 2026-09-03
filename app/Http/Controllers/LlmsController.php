<?php

namespace App\Http\Controllers;

use App\Actions\GenerateLlmsTxt;

class LlmsController extends Controller
{
    public function __invoke(GenerateLlmsTxt $generate)
    {
        return response($generate(), 200, [
            "Content-Type" => "text/plain; charset=UTF-8",
        ]);
    }
}
