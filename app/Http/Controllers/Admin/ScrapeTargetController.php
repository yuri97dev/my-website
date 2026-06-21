<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\Scrape\StoreScrapeTargetRequest;
use App\Http\Requests\Scrape\UpdateScrapeTargetRequest;
use App\Models\ScrapeTarget;

class ScrapeTargetController extends Controller
{
    public function index(Request $request)
    {
        $perPage = min($request->integer("per_page", 15), 100);

        $paginator = ScrapeTarget::query()
            ->select([
                "id",
                "driver",
                "name",
                "locale",
                "http_method",
                "list_url",
                "list_item_selector",
                "item_title_selector",
                "item_link_selector",
                "content_selector",
                "is_enabled",
                "created_at",
                "updated_at",
            ])
            ->orderByDesc("id")
            ->paginate($perPage);

        return response()->json([
            "current_page" => $paginator->currentPage(),
            "last_page" => $paginator->lastPage(),
            "per_page" => $paginator->perPage(),
            "total" => $paginator->total(),
            "scrape_targets" => $paginator->items(),
        ], 200);
    }

    public function store(StoreScrapeTargetRequest $request)
    {
        $target = ScrapeTarget::create($request->validated());

        return response()->json([
            "scrape_target" => $target,
        ], 201);
    }

    public function update(UpdateScrapeTargetRequest $request, ScrapeTarget $scrapeTarget)
    {
        $scrapeTarget->update($request->validated());

        return response()->json([
            "scrape_target" => $scrapeTarget->fresh(),
        ], 200);
    }

    public function disable(ScrapeTarget $scrapeTarget)
    {
        $scrapeTarget->update(["is_enabled" => false]);

        return response()->json([
            "scrape_target" => $scrapeTarget->fresh(),
            "message" => "Target desabilitado com sucesso.",
        ], 200);
    }
}
