<?php

namespace App\Http\Controllers\Sgc;

use App\Http\Controllers\Controller;
use App\Http\Requests\Sgc\StoreBrandRequest;
use App\Http\Requests\Sgc\UpdateBrandRequest;
use App\Models\SgcBrand;
use Illuminate\Http\Request;

class BrandController extends Controller
{
    public function index(Request $request)
    {
        $per_page = min($request->integer("per_page", 15), 100);

        $paginator = SgcBrand::query()
            ->orderBy("name")
            ->paginate($per_page);

        return response()->json([
            "current_page" => $paginator->currentPage(),
            "last_page" => $paginator->lastPage(),
            "per_page" => $paginator->perPage(),
            "total" => $paginator->total(),
            "brands" => $paginator->items(),
        ], 200);
    }

    public function store(StoreBrandRequest $request)
    {
        $brand = SgcBrand::create($request->validated());

        return response()->json([
            "brand" => $brand,
        ], 201);
    }

    public function update(UpdateBrandRequest $request, SgcBrand $sgc_brand)
    {
        $sgc_brand->update($request->validated());

        return response()->json([
            "brand" => $sgc_brand->fresh(),
        ], 200);
    }

    public function destroy(SgcBrand $sgc_brand)
    {
        $sgc_brand->delete();

        return response()->json([
            "message" => "Marca removida com sucesso.",
        ], 200);
    }
}
