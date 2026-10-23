<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\GenerateArticleShortlistJob;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Throwable;

class ArticleShortlistController extends Controller
{
    public function __invoke(): JsonResponse
    {
        return response()->json($this->state());
    }

    public function store(): JsonResponse
    {
        return Cache::lock("article-shortlist:start", 10)->block(3, function () {
            $state = $this->state();

            if( in_array($state["status"], ["queued", "running"], true) ) {
                return response()->json($state, 202);
            }

            $state = [
                "run_id" => (string) Str::uuid(),
                "status" => "queued",
                "processed" => 0,
                "total" => 0,
                "data" => $state["data"] ?? [],
                "count" => $state["count"] ?? 0,
                "expires_at" => now()->addSeconds(GenerateArticleShortlistJob::TTL)->timestamp,
            ];

            Cache::put(
                GenerateArticleShortlistJob::CACHE_KEY,
                $state,
                GenerateArticleShortlistJob::TTL
            );

            try {
                GenerateArticleShortlistJob::dispatch($state["run_id"]);
            } catch(Throwable $e) {
                Cache::put(
                    GenerateArticleShortlistJob::CACHE_KEY,
                    array_replace($state, ["status" => "failed"]),
                    GenerateArticleShortlistJob::TTL
                );

                report($e);

                return response()->json([
                    "message" => "Não foi possível iniciar a seleção."
                ], 503);
            }

            return response()->json($state, 202);
        });
    }

    private function state(): array
    {
        $state = Cache::get(
            GenerateArticleShortlistJob::CACHE_KEY,
            ["status" => "idle", "data" => [], "count" => 0]
        );

        if( in_array($state["status"], ["queued", "running"], true) && ($state["expires_at"] ?? 0) < now()->timestamp ) {
            $state["status"] = "failed";
            $state["message"] = "A seleção excedeu o tempo limite. Verifique se o worker do Laya está ativo.";
        }

        return $state;
    }
}
