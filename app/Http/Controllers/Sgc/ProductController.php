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
    private const PIVOT_FIELDS = [
        "notes",
        "sale_price",
        "cost_price",
        "currency",
    ];

    public function index(Request $request)
    {
        $request->validate([
            "company_id" => ["nullable", "integer", "exists:sgc_companies,id"],
            "id" => ["nullable", "integer"],
            "name" => ["nullable", "string", "max:255"],
            "search" => ["nullable", "string", "max:255"],
        ]);

        if( $request->filled("company_id") ) {
            $company = SgcCompany::query()->findOrFail($request->integer("company_id"));

            abort_unless(
                $company->isAccessibleBy($request->user()),
                Response::HTTP_FORBIDDEN
            );
        }

        $per_page = min($request->integer("per_page", 15), 100);

        $paginator = SgcProduct::query()
            ->whereHas("companies", function ($query) use ($request) {
                $query->accessibleBy($request->user());

                if( $request->filled("company_id") ) {
                    $query->where("sgc_companies.id", $request->integer("company_id"));
                }
            })
            ->when($request->filled("id"), function ($query) use ($request) {
                $query->where("sgc_products.id", $request->integer("id"));
            })
            ->when($request->filled("name"), function ($query) use ($request) {
                $query->where("name", "ilike", "%".$request->input("name")."%");
            })
            ->when($request->filled("search"), function ($query) use ($request) {
                $term = "%".$request->input("search")."%";

                $query->where("name", "ilike", $term);
            })
            ->with([
                "brand",
                "category",
                "companies" => function ($query) use ($request) {
                    $query->accessibleBy($request->user());

                    if( $request->filled("company_id") ) {
                        $query->where("sgc_companies.id", $request->integer("company_id"));
                    }
                },
            ])
            ->orderByDesc("sgc_products.id")
            ->paginate($per_page);

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
            ...self::PIVOT_FIELDS,
        ])->all());

        $product->companies()->attach($company_id, $this->pivotPayload($validated));

        $product->load(["brand", "category"]);

        return response()->json([
            "product" => $product,
        ], 201);
    }

    public function show(Request $request, SgcProduct $sgc_product)
    {
        abort_unless(
            $this->productAccessibleByUser($sgc_product, $request->user()),
            Response::HTTP_FORBIDDEN
        );

        $sgc_product->load([
            "brand",
            "category",
            "companies" => function ($query) use ($request) {
                $query->accessibleBy($request->user());
            },
        ]);

        return response()->json([
            "product" => $sgc_product,
        ], 200);
    }

    public function update(UpdateProductRequest $request, SgcProduct $sgc_product)
    {
        $validated = $request->validated();
        $company_id = $validated["company_id"];

        $sgc_product->update(collect($validated)->except([
            "company_id",
            ...self::PIVOT_FIELDS,
        ])->all());

        $pivot_data = $this->pivotPayload($validated);

        if( !empty($pivot_data) ) {
            $sgc_product->companies()->updateExistingPivot($company_id, $pivot_data);
        }

        $sgc_product->load(["brand", "category"]);

        return response()->json([
            "product" => $sgc_product,
        ], 200);
    }

    public function destroy(Request $request, SgcProduct $sgc_product)
    {
        abort_unless(
            $this->productAccessibleByUser($sgc_product, $request->user()),
            Response::HTTP_FORBIDDEN
        );

        $sgc_product->delete();

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

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function pivotPayload(array $validated): array
    {
        $payload = collect($validated)->only(self::PIVOT_FIELDS)->all();

        if( !array_key_exists("currency", $payload) || $payload["currency"] === null ) {
            $payload["currency"] = "BRL";
        }

        return $payload;
    }
}
