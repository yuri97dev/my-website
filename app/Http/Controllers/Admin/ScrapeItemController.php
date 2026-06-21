<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ScrapeItem;

class ScrapeItemController extends Controller
{
    public function index(Request $request)
    {
        $perPage = min($request->integer("per_page", 15), 100);

        $paginator = ScrapeItem::query()
            ->select([
                "id",
                "scrape_target_id",
                "post_id",
                "url",
                "title",
                "translated_title",
                "is_enabled",
                "created_at",
                "updated_at",
            ])
            ->with("target:id,name")
            ->orderByDesc("id")
            ->paginate($perPage);

        return response()->json([
            "current_page" => $paginator->currentPage(),
            "last_page" => $paginator->lastPage(),
            "per_page" => $paginator->perPage(),
            "total" => $paginator->total(),
            "scrape_items" => $paginator->items(),
        ], 200);
    }

    public function show(ScrapeItem $scrapeItem)
    {
        $scrapeItem->load("target:id,name,driver");

        return response()->json([
            "scrape_item" => $scrapeItem,
        ], 200);
    }

    public function disable(ScrapeItem $scrapeItem)
    {
        $scrapeItem->update(["is_enabled" => false]);

        return response()->json([
            "scrape_item" => $scrapeItem->fresh(),
            "message" => "Item inativado com sucesso.",
        ], 200);
    }
}
