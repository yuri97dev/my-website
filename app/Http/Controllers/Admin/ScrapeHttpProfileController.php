<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ScrapeHttpProfile\StoreScrapeHttpProfileRequest;
use App\Http\Requests\ScrapeHttpProfile\UpdateScrapeHttpProfileRequest;
use App\Models\ScrapeHttpProfile;
use Illuminate\Http\Request;

class ScrapeHttpProfileController extends Controller
{
    public function index(Request $request)
    {
        $per_page = min($request->integer("per_page", 15), 100);

        $paginator = ScrapeHttpProfile::query()
            ->select([
                "id",
                "name",
                "headers",
                "is_enabled",
                "created_at",
                "updated_at",
            ])
            ->orderByDesc("id")
            ->paginate($per_page);

        return response()->json([
            "current_page" => $paginator->currentPage(),
            "last_page" => $paginator->lastPage(),
            "per_page" => $paginator->perPage(),
            "total" => $paginator->total(),
            "scrape_http_profiles" => $paginator->items(),
        ], 200);
    }

    public function store(StoreScrapeHttpProfileRequest $request)
    {
        $scrape_http_profile = ScrapeHttpProfile::create($request->validated());

        return response()->json([
            "scrape_http_profile" => $scrape_http_profile,
        ], 201);
    }

    public function update(UpdateScrapeHttpProfileRequest $request, ScrapeHttpProfile $scrape_http_profile)
    {
        $scrape_http_profile->update($request->validated());

        return response()->json([
            "scrape_http_profile" => $scrape_http_profile->fresh(),
        ], 200);
    }

    public function destroy(ScrapeHttpProfile $scrape_http_profile)
    {
        $scrape_http_profile->delete();

        return response()->json([
            "message" => "Perfil HTTP removido com sucesso.",
        ], 200);
    }
}
