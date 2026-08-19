<?php

namespace App\Http\Controllers\Sgc;

use App\Helpers\SearchQuery;
use App\Http\Controllers\Controller;
use App\Http\Requests\Sgc\PayOrderInstallmentRequest;
use App\Http\Requests\Sgc\StoreOrderRequest;
use App\Http\Requests\Sgc\UpdateOrderRequest;
use App\Models\SgcCompany;
use App\Models\SgcOrder;
use App\Models\SgcOrderInstallment;
use App\Models\SgcProduct;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
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
                DB::raw("coalesce(nullif(sgc_companies.trade_name, ''), sgc_companies.legal_name) as company_name"),
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

            if( $order->isConfirmed() ) {
                $this->deductStock($order, $items);
            }

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
        if( !$sgc_order->canBeEdited() ) return response()->json([
            "message" => "Pedidos confirmados ou cancelados não podem ser alterados.",
        ], 422);

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

            if( $sgc_order->isConfirmed() ) {
                $this->deductStock($sgc_order, $items);
            }
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

        DB::transaction(function () use ($sgc_order) {
            if( $sgc_order->isConfirmed() ) {
                $this->restoreStock($sgc_order);
            }

            $sgc_order->delete();
        });

        return response()->json([
            "message" => "Pedido removido com sucesso.",
        ], 200);
    }

    public function cancel(Request $request, SgcOrder $sgc_order)
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

        if( $sgc_order->isCancelled() ) return response()->json([
            "message" => "Este pedido já está cancelado.",
        ], 422);

        DB::transaction(function () use ($sgc_order) {
            $was_confirmed = $sgc_order->isConfirmed();

            $sgc_order->update([
                "status" => SgcOrder::STATUS_CANCELLED,
            ]);

            $sgc_order->clearInstallmentPayments();

            if( $was_confirmed ) {
                $this->restoreStock($sgc_order);
            }
        });

        $this->loadOrder($sgc_order);

        return response()->json([
            "order" => $sgc_order,
            "message" => "Pedido cancelado com sucesso.",
        ], 200);
    }

    public function payInstallment(
        PayOrderInstallmentRequest $request,
        SgcOrder $sgc_order,
        SgcOrderInstallment $sgc_order_installment,
    ) {
        $validated = $request->validated();

        DB::transaction(function () use ($sgc_order, $sgc_order_installment, $validated) {
            $installment = SgcOrderInstallment::query()
                ->whereKey($sgc_order_installment->id)
                ->where("order_id", $sgc_order->id)
                ->lockForUpdate()
                ->firstOrFail();

            $amount = round((float) $validated["amount"], 2);
            $remaining = $installment->remainingAmount();

            if( $amount > $remaining ) {
                throw ValidationException::withMessages([
                    "amount" => "O valor da baixa não pode ser maior que o restante da parcela.",
                ]);
            }

            $installment->applyPayment($amount);
        });

        $this->loadOrder($sgc_order);

        return response()->json([
            "order" => $sgc_order,
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
            "installments" => function ($query) {
                $query->orderBy("installment_number");
            },
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
     * @param  array<int, array<string, mixed>>  $items
     */
    private function deductStock(SgcOrder $order, array $items): void
    {
        $this->adjustStock((int) $order->company_id, $items, -1);
    }

    private function restoreStock(SgcOrder $order): void
    {
        $items = $order->items()->get(["product_id", "quantity"])->map(function ($item) {
            return [
                "product_id" => $item->product_id,
                "quantity" => $item->quantity,
            ];
        })->all();

        $this->adjustStock((int) $order->company_id, $items, 1);
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     */
    private function adjustStock(int $company_id, array $items, int $multiplier): void
    {
        $quantities = [];

        foreach( $items as $item ) {
            $product_id = $item["product_id"] ?? null;

            if( !$product_id ) {
                continue;
            }

            $quantities[$product_id] = ($quantities[$product_id] ?? 0)
                + (float) ($item["quantity"] ?? 0);
        }

        foreach( $quantities as $product_id => $quantity ) {
            $delta = round($quantity * $multiplier, 3);

            if( $delta == 0 ) {
                continue;
            }

            $pivots = DB::table("sgc_company_product")
                ->where("company_id", $company_id)
                ->where("product_id", $product_id)
                ->orderBy("id")
                ->lockForUpdate()
                ->get();

            if( $pivots->isEmpty() ) {
                throw ValidationException::withMessages([
                    "items" => "Produto não vinculado a esta empresa.",
                ]);
            }

            if( $delta < 0 ) {
                $this->consumeStock($pivots, abs($delta), (int) $product_id);

                continue;
            }

            $pivot = $pivots->first();

            DB::table("sgc_company_product")
                ->where("id", $pivot->id)
                ->update([
                    "stock_quantity" => round((float) $pivot->stock_quantity + $delta, 3),
                    "updated_at" => now(),
                ]);
        }
    }

    /**
     * @param  Collection<int, object>  $pivots
     */
    private function consumeStock(Collection $pivots, float $needed, int $product_id): void
    {
        $available = round((float) $pivots->sum("stock_quantity"), 3);
        $needed = round($needed, 3);

        if( $needed > $available ) {
            $name = SgcProduct::query()->whereKey($product_id)->value("name");

            throw ValidationException::withMessages([
                "items" => $name
                    ? "Estoque insuficiente para o produto {$name}."
                    : "Estoque insuficiente para um dos produtos do pedido.",
            ]);
        }

        $remaining = $needed;

        foreach( $pivots as $pivot ) {
            if( $remaining <= 0 ) {
                break;
            }

            $current = (float) $pivot->stock_quantity;
            $take = min($current, $remaining);

            if( $take <= 0 ) {
                continue;
            }

            DB::table("sgc_company_product")
                ->where("id", $pivot->id)
                ->update([
                    "stock_quantity" => round($current - $take, 3),
                    "updated_at" => now(),
                ]);

            $remaining = round($remaining - $take, 3);
        }
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function syncInstallments(SgcOrder $order, array $validated): void
    {
        $order->installments()->delete();

        $installments = $validated["installments"] ?? [];

        if( is_array($installments) && count($installments) > 0 ) {
            foreach( $installments as $installment ) {
                $order->installments()->create([
                    "installment_number" => $installment["installment_number"],
                    "due_date" => $installment["due_date"] ?? null,
                    "amount" => $installment["amount"],
                    "paid_amount" => 0,
                ]);
            }

            return;
        }

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
                "paid_amount" => 0,
            ]);
        }
    }
}
