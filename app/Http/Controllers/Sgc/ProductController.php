<?php

namespace App\Http\Controllers\Sgc;

use App\Helpers\SearchQuery;
use App\Http\Controllers\Controller;
use App\Http\Requests\Sgc\StoreProductRequest;
use App\Http\Requests\Sgc\UpdateProductRequest;
use App\Models\SgcCompany;
use App\Models\SgcProduct;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class ProductController extends Controller
{
    private const PIVOT_FIELDS = [
        "unit_of_measure_id",
        "notes",
        "sale_price",
        "cost_price",
        "stock_quantity",
        "serial_number",
        "currency",
    ];

    public function index(Request $request)
    {
        $request->validate([
            "company_id" => ["nullable", "integer", "exists:sgc_companies,id"],
            "company" => ["nullable", "string", "max:255"],
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
            ->leftJoin(
                "sgc_brands",
                "sgc_products.brand_id",
                "=",
                "sgc_brands.id"
            )
            ->leftJoin(
                "sgc_product_categories",
                "sgc_products.product_category_id",
                "=",
                "sgc_product_categories.id"
            )
            ->leftJoin(
                "sgc_company_product",
                "sgc_products.id",
                "=",
                "sgc_company_product.product_id"
            )
            ->leftJoin(
                "sgc_units_of_measure",
                "sgc_company_product.unit_of_measure_id",
                "=",
                "sgc_units_of_measure.id"
            )
            ->select([
                "sgc_products.*",
                "sgc_brands.name as brand_name",
                "sgc_product_categories.name as category_name",
                "sgc_company_product.id as company_product_id",
                "sgc_company_product.sale_price",
                "sgc_company_product.cost_price",
                "sgc_company_product.stock_quantity",
                "sgc_company_product.serial_number",
                "sgc_company_product.unit_of_measure_id",
                "sgc_units_of_measure.abbreviation as unit_of_measure",
            ])
            ->when($request->filled("company_id"), function ($query) use ($request) {
                $query->where(
                    "sgc_company_product.company_id",
                    $request->integer("company_id")
                );
            })
            ->whereHas("companies", function ($query) use ($request) {
                $query->accessibleBy($request->user());

                if( $request->filled("company_id") ) {
                    $query->where("sgc_companies.id", $request->integer("company_id"));
                }

                if( $request->filled("company") ) {
                    $company = $request->input("company");

                    $query->where(function ($query) use ($company) {
                        SearchQuery::applySubstringSearch($query, "sgc_companies.legal_name", $company);

                        $query->orWhere(function ($query) use ($company) {
                            SearchQuery::applySubstringSearch($query, "sgc_companies.trade_name", $company);
                        });
                    });
                }
            })
            ->when($request->filled("id"), function ($query) use ($request) {
                $query->where("sgc_products.id", $request->integer("id"));
            })
            ->when($request->filled("name"), function ($query) use ($request) {
                SearchQuery::applySubstringSearch(
                    $query,
                    "sgc_products.name",
                    $request->input("name")
                );
            })
            ->when($request->filled("search"), function ($query) use ($request) {
                SearchQuery::applyWordSearch(
                    $query,
                    "concat_ws(' ', sgc_products.name, sgc_brands.name, sgc_product_categories.name)",
                    $request->input("search")
                );
            })
            ->with([
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

        if( !empty($validated["product_id"]) ) {
            $product = SgcProduct::query()->findOrFail($validated["product_id"]);
        } else {
            $product = SgcProduct::create(collect($validated)->except([
                "company_id",
                "product_id",
                ...self::PIVOT_FIELDS,
            ])->all());
        }

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
            "company_product_id",
            "product_id",
            ...self::PIVOT_FIELDS,
        ])->all());

        $pivot_data = $this->pivotPayload($validated);

        if( !empty($pivot_data) ) {
            $pivot = DB::table("sgc_company_product")
                ->where("product_id", $sgc_product->id)
                ->where("company_id", $company_id)
                ->when(
                    !empty($validated["company_product_id"]),
                    fn ($query) => $query->where("id", $validated["company_product_id"])
                )
                ->first();

            abort_unless($pivot, Response::HTTP_NOT_FOUND);

            DB::table("sgc_company_product")
                ->where("id", $pivot->id)
                ->update($pivot_data);
        }

        $sgc_product->load(["brand", "category"]);

        return response()->json([
            "product" => $sgc_product,
        ], 200);
    }

    public function destroy(Request $request, SgcProduct $sgc_product)
    {
        $request->validate([
            "company_id" => ["required", "integer", "exists:sgc_companies,id"],
            "company_product_id" => ["nullable", "integer", "exists:sgc_company_product,id"],
        ]);

        $company = SgcCompany::query()->findOrFail($request->integer("company_id"));

        abort_unless(
            $company->canBeManagedBy($request->user()),
            Response::HTTP_FORBIDDEN
        );

        $pivot = DB::table("sgc_company_product")
            ->where("product_id", $sgc_product->id)
            ->where("company_id", $company->id)
            ->when(
                $request->filled("company_product_id"),
                fn ($query) => $query->where("id", $request->integer("company_product_id"))
            )
            ->first();

        abort_unless($pivot, Response::HTTP_NOT_FOUND);

        DB::table("sgc_company_product")->where("id", $pivot->id)->delete();

        if( $sgc_product->companies()->count() === 0 ) {
            $sgc_product->delete();
        }

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

        if( !array_key_exists("stock_quantity", $payload) || $payload["stock_quantity"] === null ) {
            $payload["stock_quantity"] = 0;
        }

        return $payload;
    }
}
