<?php

namespace App\Http\Controllers\Sgc;

use App\Http\Controllers\Controller;
use App\Http\Requests\Sgc\StoreProductRequest;
use App\Http\Requests\Sgc\UpdateProductRequest;
use App\Models\SgcCompany;
use App\Models\SgcProduct;
use App\Models\User;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            "company_id" => ["required", "integer", "exists:sgc_companies,id"],
        ]);

        $company = SgcCompany::query()->findOrFail($request->integer("company_id"));

        abort_unless(
            $company->isAccessibleBy($request->user()),
            Response::HTTP_FORBIDDEN
        );

        $perPage = min($request->integer("per_page", 15), 100);

        $paginator = $company->products()
            ->with(["brand", "category"])
            ->orderByDesc("sgc_products.id")
            ->paginate($perPage);

        return response()->json([
            "current_page" => $paginator->currentPage(),
            "last_page" => $paginator->lastPage(),
            "per_page" => $paginator->perPage(),
            "total" => $paginator->total(),
            "products" => $paginator->items(),
        ], 200);
    }

    public function store(StoreProductRequest $request)
    {
        $validated = $request->validated();
        $company_id = $validated["company_id"];

        $product = SgcProduct::create(collect($validated)->except([
            "company_id",
            "notes",
        ])->all());

        $product->companies()->attach($company_id, [
            "notes" => $validated["notes"] ?? null,
        ]);

        $product->load(["brand", "category"]);

        return response()->json([
            "product" => $product,
        ], 201);
    }

    public function show(Request $request, SgcProduct $sgcProduct)
    {
        abort_unless(
            $this->productAccessibleByUser($sgcProduct, $request->user()),
            Response::HTTP_FORBIDDEN
        );

        $sgcProduct->load(["brand", "category"]);

        return response()->json([
            "product" => $sgcProduct,
        ], 200);
    }

    public function update(UpdateProductRequest $request, SgcProduct $sgcProduct)
    {
        $validated = $request->validated();
        $company_id = $validated["company_id"];

        $sgcProduct->update(collect($validated)->except([
            "notes",
            "company_id",
        ])->all());

        if( array_key_exists("notes", $validated) ) {
            $sgcProduct->companies()->updateExistingPivot($company_id, [
                "notes" => $validated["notes"],
            ]);
        }

        $sgcProduct->load(["brand", "category"]);

        return response()->json([
            "product" => $sgcProduct,
        ], 200);
    }

    public function destroy(Request $request, SgcProduct $sgcProduct)
    {
        abort_unless(
            $this->productAccessibleByUser($sgcProduct, $request->user()),
            Response::HTTP_FORBIDDEN
        );

        $sgcProduct->delete();

        return response()->json([
            "message" => "Produto removido com sucesso.",
        ], 200);
    }

    private function productAccessibleByUser(SgcProduct $product, User $user): bool
    {
        return $product->companies()
            ->accessibleBy($user)
            ->exists();
    }
}
