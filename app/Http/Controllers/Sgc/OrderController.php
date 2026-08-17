<?php

namespace App\Http\Controllers\Sgc;

use App\Helpers\SearchQuery;
use App\Http\Controllers\Controller;
use App\Http\Requests\Sgc\StoreOrderRequest;
use App\Http\Requests\Sgc\UpdateOrderRequest;
use App\Models\SgcCompany;
use App\Models\SgcOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            "company_id" => ["nullable", "integer", "exists:sgc_companies,id"],
            "company" => ["nullable", "string", "max:255"],
            "id" => ["nullable", "integer"],
            "customer" => ["nullable", "string", "max:255"],
            "status" => ["nullable", "string", Rule::in(SgcOrder::STATUSES)],
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

        $paginator = SgcOrder::query()
            ->leftJoin(
                "sgc_companies",
                "sgc_orders.company_id",
                "=",
                "sgc_companies.id"
            )
            ->leftJoin(
                "sgc_customers",
                "sgc_orders.customer_id",
                "=",
                "sgc_customers.id"
            )
            ->leftJoin(
                "users",
                "sgc_orders.created_by_id",
                "=",
                "users.id"
            )
            ->select([
                "sgc_orders.*",
                DB::raw("coalesce(sgc_companies.trade_name, sgc_companies.legal_name) as company_name"),
                "sgc_customers.name as customer_name",
                "users.name as created_by_name",
            ])
            ->whereHas("company", function ($query) use ($request) {
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
                $query->where("sgc_orders.id", $request->integer("id"));
            })
            ->when($request->filled("customer"), function ($query) use ($request) {
                SearchQuery::applySubstringSearch(
                    $query,
                    "sgc_customers.name",
                    $request->input("customer")
                );
            })
            ->when($request->filled("status"), function ($query) use ($request) {
                $query->where("sgc_orders.status", $request->input("status"));
            })
            ->when($request->filled("search"), function ($query) use ($request) {
                SearchQuery::applyWordSearch(
                    $query,
                    "concat_ws(' ', sgc_customers.name, sgc_companies.legal_name, sgc_companies.trade_name, sgc_orders.uuid, sgc_orders.status)",
                    $request->input("search")
                );
            })
            ->orderByDesc("sgc_orders.id")
            ->paginate($per_page);

        return response()->json([
            "current_page" => $paginator->currentPage(),
            "last_page" => $paginator->lastPage(),
            "per_page" => $paginator->perPage(),
            "total" => $paginator->total(),
            "orders" => $paginator->items(),
        ], 200);
    }

    public function store(StoreOrderRequest $request)
    {
        $validated = $request->validated();
        $items = $validated["items"] ?? [];

        $order = DB::transaction(function () use ($request, $validated, $items) {
            $order = SgcOrder::create([
                "company_id" => $validated["company_id"],
                "customer_id" => $validated["customer_id"] ?? null,
                "created_by_id" => $request->user()->id,
                "customer_address_id" => $validated["customer_address_id"] ?? null,
                "payment_method_id" => $validated["payment_method_id"] ?? null,
                "uuid" => (string) Str::uuid(),
                "status" => $validated["status"] ?? SgcOrder::STATUS_DRAFT,
                "discount_amount" => $validated["discount_amount"] ?? 0,
                "total" => $this->calculateTotal($items, $validated["discount_amount"] ?? 0),
                "currency" => $validated["currency"] ?? "BRL",
                "notes" => $validated["notes"] ?? null,
                "ordered_at" => $this->resolveOrderedAt($validated),
            ]);

            $order->items()->createMany($this->itemsPayload($items));

            $this->syncInstallments($order, $validated);

            return $order;
        });

        $this->loadOrder($order);

        return response()->json([
            "order" => $order,
        ], 201);
    }

    public function show(Request $request, SgcOrder $sgc_order)
    {
        abort_unless(
            $sgc_order->isAccessibleBy($request->user()),
            Response::HTTP_FORBIDDEN
        );

        $this->loadOrder($sgc_order);

        return response()->json([
            "order" => $sgc_order,
        ], 200);
    }

    public function update(UpdateOrderRequest $request, SgcOrder $sgc_order)
    {
        $validated = $request->validated();
        $items = $validated["items"] ?? [];

        DB::transaction(function () use ($sgc_order, $validated, $items) {
            $sgc_order->update([
                "customer_id" => $validated["customer_id"] ?? null,
                "customer_address_id" => $validated["customer_address_id"] ?? null,
                "payment_method_id" => $validated["payment_method_id"] ?? null,
                "status" => $validated["status"] ?? $sgc_order->status,
                "discount_amount" => $validated["discount_amount"] ?? 0,
                "total" => $this->calculateTotal($items, $validated["discount_amount"] ?? 0),
                "currency" => $validated["currency"] ?? $sgc_order->currency,
                "notes" => $validated["notes"] ?? null,
                "ordered_at" => $this->resolveOrderedAt($validated, $sgc_order),
            ]);

            $sgc_order->items()->delete();
            $sgc_order->items()->createMany($this->itemsPayload($items));

            $this->syncInstallments($sgc_order, $validated);
        });

        $this->loadOrder($sgc_order);

        return response()->json([
            "order" => $sgc_order,
        ], 200);
    }

    public function destroy(Request $request, SgcOrder $sgc_order)
    {
        abort_unless(
            $sgc_order->isAccessibleBy($request->user()),
            Response::HTTP_FORBIDDEN
        );

        $company = SgcCompany::query()->findOrFail($sgc_order->company_id);

        abort_unless(
            $company->canBeManagedBy($request->user()),
            Response::HTTP_FORBIDDEN
        );

        $sgc_order->delete();

        return response()->json([
            "message" => "Pedido removido com sucesso.",
        ], 200);
    }

    private function loadOrder(SgcOrder $order): void
    {
        $order->load([
            "company:id,legal_name,trade_name",
            "customer:id,name,nickname",
            "customerAddress",
            "createdBy:id,name,email",
            "paymentMethod:id,slug,name,surcharge_percentage",
            "items.product:id,name",
            "items.unitOfMeasure:id,name,abbreviation",
            "installments",
        ]);
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @return array<int, array<string, mixed>>
     */
    private function itemsPayload(array $items): array
    {
        return collect($items)->map(function ($item) {
            return [
                "product_id" => $item["product_id"] ?? null,
                "unit_of_measure_id" => $item["unit_of_measure_id"] ?? null,
                "quantity" => $item["quantity"],
                "unit_price" => $item["unit_price"],
                "discount_amount" => $item["discount_amount"] ?? 0,
            ];
        })->all();
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     */
    private function calculateTotal(array $items, mixed $order_discount): float
    {
        $items_total = 0;

        foreach( $items as $item ) {
            $quantity = (float) ($item["quantity"] ?? 0);
            $unit_price = (float) ($item["unit_price"] ?? 0);
            $discount = (float) ($item["discount_amount"] ?? 0);

            $items_total += ($quantity * $unit_price) - $discount;
        }

        return max(0, round($items_total - (float) $order_discount, 2));
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function resolveOrderedAt(array $validated, ?SgcOrder $order = null): mixed
    {
        $status = $validated["status"] ?? $order?->status ?? SgcOrder::STATUS_DRAFT;

        if( $status === SgcOrder::STATUS_CONFIRMED ) {
            return $order?->ordered_at ?? now();
        }

        if( $status === SgcOrder::STATUS_CANCELLED ) {
            return $order?->ordered_at;
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function syncInstallments(SgcOrder $order, array $validated): void
    {
        $order->installments()->delete();

        $count = (int) ($validated["installments_count"] ?? 1);

        if( $count < 1 ) {
            return;
        }

        $total = (float) $order->total;
        $base_amount = floor(($total / $count) * 100) / 100;
        $remaining = $total;

        for( $i = 1; $i <= $count; $i++ ) {
            $amount = $i === $count
                ? round($remaining, 2)
                : $base_amount;

            $remaining -= $amount;

            $order->installments()->create([
                "installment_number" => $i,
                "due_date" => now()->addMonths($i)->toDateString(),
                "amount" => $amount,
            ]);
        }
    }
}
