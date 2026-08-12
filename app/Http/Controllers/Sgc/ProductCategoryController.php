<?php

namespace App\Http\Controllers\Sgc;

use App\Http\Controllers\Controller;
use App\Http\Requests\Sgc\StoreProductCategoryRequest;
use App\Http\Requests\Sgc\UpdateProductCategoryRequest;
use App\Models\SgcProductCategory;
use Illuminate\Http\Request;

class ProductCategoryController extends Controller
{
    public function index(Request $request)
    {
        $per_page = min($request->integer("per_page", 15), 100);

        $paginator = SgcProductCategory::query()
            ->orderBy("name")
            ->paginate($per_page);

        return response()->json([
            "current_page" => $paginator->currentPage(),
            "last_page" => $paginator->lastPage(),
            "per_page" => $paginator->perPage(),
            "total" => $paginator->total(),
            "product_categories" => $paginator->items(),
        ], 200);
    }

    public function store(StoreProductCategoryRequest $request)
    {
        $category = SgcProductCategory::create($request->validated());

        return response()->json([
            "product_category" => $category,
        ], 201);
    }

    public function update(UpdateProductCategoryRequest $request, SgcProductCategory $sgc_product_category)
    {
        $sgc_product_category->update($request->validated());

        return response()->json([
            "product_category" => $sgc_product_category->fresh(),
        ], 200);
    }

    public function destroy(SgcProductCategory $sgc_product_category)
    {
        $sgc_product_category->delete();

        return response()->json([
            "message" => "Categoria removida com sucesso.",
        ], 200);
    }
}
