<?php

namespace App\Http\Controllers\Sgc;

use App\Http\Controllers\Controller;
use App\Http\Requests\Sgc\StoreProductRequest;
use App\Http\Requests\Sgc\UpdateProductRequest;
use App\Models\SgcCompany;
use App\Models\SgcCompanyProduct;
use App\Models\SgcCompanyProductStockMovement;
use App\Models\SgcProduct;
use App\Models\User;
use App\Services\Sgc\ProductListingService;
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

    public function __construct(
        private ProductListingService $listing,
    ) {}

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

        $paginator = $this->listing->grouped($request);

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

        $product = DB::transaction(function () use ($request, $validated, $company_id) {
            if( !empty($validated["product_id"]) ) {
                $product = SgcProduct::query()->findOrFail($validated["product_id"]);
            } else {
                $product = SgcProduct::create(collect($validated)->except([
                    "company_id",
                    "product_id",
                    ...self::PIVOT_FIELDS,
                ])->all());
            }

            $pivot = new SgcCompanyProduct([
                "company_id" => $company_id,
                "product_id" => $product->id,
                ...$this->pivotPayload($validated),
            ]);

            $pivot->setStockMovementMeta(
                SgcCompanyProductStockMovement::TYPE_ENTRY,
                "Estoque inicial",
                null,
                $request->user()->id,
            );

            $pivot->save();

            return $product;
        });

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

        DB::transaction(function () use ($request, $validated, $sgc_product, $company_id) {
            $sgc_product->update(collect($validated)->except([
                "company_id",
                "company_product_id",
                "product_id",
                "stock_movement_reason",
                ...self::PIVOT_FIELDS,
            ])->all());

            $pivot_data = $this->pivotPayload($validated);

            if( empty($pivot_data) ) {
                return;
            }

            $pivot = $this->findCompanyProduct(
                $sgc_product,
                $company_id,
                $validated["company_product_id"] ?? null,
            );

            abort_unless($pivot, Response::HTTP_NOT_FOUND);

            if(
                array_key_exists("stock_quantity", $pivot_data)
                && SgcCompanyProduct::quantitiesDiffer(
                    $pivot->stock_quantity,
                    $pivot_data["stock_quantity"]
                )
            ) {
                $pivot->setStockMovementMeta(
                    SgcCompanyProductStockMovement::TYPE_ADJUSTMENT,
                    $validated["stock_movement_reason"] ?? null,
                    null,
                    $request->user()->id,
                );
            }

            $pivot->fill($pivot_data);
            $pivot->save();
        });

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
            "stock_movement_reason" => ["required", "string", "min:3", "max:500"],
        ], [
            "stock_movement_reason.required" => "Informe o motivo da exclusão do produto.",
            "stock_movement_reason.string" => "O motivo da exclusão deve ser um texto válido.",
            "stock_movement_reason.min" => "O motivo da exclusão deve ter pelo menos 3 caracteres.",
            "stock_movement_reason.max" => "O motivo da exclusão não pode ter mais de 500 caracteres.",
        ]);

        $company = SgcCompany::query()->findOrFail($request->integer("company_id"));

        abort_unless(
            $company->canBeManagedBy($request->user()),
            Response::HTTP_FORBIDDEN
        );

        DB::transaction(function () use ($request, $sgc_product, $company) {
            $pivot = $this->findCompanyProduct(
                $sgc_product,
                $company->id,
                $request->integer("company_product_id") ?: null,
            );

            abort_unless($pivot, Response::HTTP_NOT_FOUND);

            $pivot->setStockMovementMeta(
                SgcCompanyProductStockMovement::TYPE_DELETION,
                $request->input("stock_movement_reason"),
                null,
                $request->user()->id,
            );

            $pivot->delete();

            if( $sgc_product->companies()->count() === 0 ) {
                $sgc_product->delete();
            }
        });

        return response()->json([
            "message" => "Produto removido com sucesso.",
        ], 200);
    }

    public function stockMovements(Request $request, SgcProduct $sgc_product)
    {
        abort_unless(
            $this->productAccessibleByUser($sgc_product, $request->user()),
            Response::HTTP_FORBIDDEN
        );

        $request->validate([
            "company_id" => ["required", "integer", "exists:sgc_companies,id"],
            "company_product_id" => ["nullable", "integer", "exists:sgc_company_product,id"],
        ]);

        $company = SgcCompany::query()->findOrFail($request->integer("company_id"));

        abort_unless(
            $company->isAccessibleBy($request->user()),
            Response::HTTP_FORBIDDEN
        );

        $per_page = min($request->integer("per_page", 50), 100);

        $paginator = SgcCompanyProductStockMovement::query()
            ->where("product_id", $sgc_product->id)
            ->where("company_id", $company->id)
            ->when(
                $request->filled("company_product_id"),
                fn ($query) => $query->where("company_product_id", $request->integer("company_product_id"))
            )
            ->with([
                "user:id,name",
            ])
            ->orderByDesc("id")
            ->paginate($per_page);

        return response()->json([
            "current_page" => $paginator->currentPage(),
            "last_page" => $paginator->lastPage(),
            "per_page" => $paginator->perPage(),
            "total" => $paginator->total(),
            "stock_movements" => $paginator->items(),
        ], 200);
    }

    private function findCompanyProduct(
        SgcProduct $product,
        int $company_id,
        mixed $company_product_id = null,
    ): ?SgcCompanyProduct {
        return SgcCompanyProduct::query()
            ->where("product_id", $product->id)
            ->where("company_id", $company_id)
            ->when(
                $company_product_id,
                fn ($query) => $query->where("id", $company_product_id)
            )
            ->first();
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
