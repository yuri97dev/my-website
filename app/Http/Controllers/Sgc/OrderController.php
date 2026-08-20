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
use App\Models\SgcPaymentMethod;
use App\Services\Sgc\ProductStockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class OrderController extends Controller
{
    public function __construct(
        private ProductStockService $stock,
    ) {}

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
                $this->stock->deductForSale($order, $items, $request->user());
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

        DB::transaction(function () use ($request, $sgc_order, $validated, $items) {
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
                $this->stock->deductForSale($sgc_order, $items, $request->user());
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

        $request->validate([
            "stock_movement_reason" => ["required", "string", "min:3", "max:500"],
        ], $this->stockMovementReasonMessages("exclusão do pedido"));

        DB::transaction(function () use ($request, $sgc_order) {
            if( $sgc_order->isConfirmed() ) {
                $this->stock->restoreForDeletion(
                    $sgc_order,
                    $request->input("stock_movement_reason"),
                    $request->user(),
                );
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

        $request->validate([
            "stock_movement_reason" => ["required", "string", "min:3", "max:500"],
        ], $this->stockMovementReasonMessages("cancelamento"));

        DB::transaction(function () use ($request, $sgc_order) {
            $was_confirmed = $sgc_order->isConfirmed();

            $sgc_order->update([
                "status" => SgcOrder::STATUS_CANCELLED,
            ]);

            $sgc_order->clearInstallmentPayments();

            if( $was_confirmed ) {
                $this->stock->restoreForCancellation(
                    $sgc_order,
                    $request->input("stock_movement_reason"),
                    $request->user(),
                );
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
                "company_product_id" => $item["company_product_id"] ?? null,
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
     * @return array<string, string>
     */
    private function stockMovementReasonMessages(string $action): array
    {
        return [
            "stock_movement_reason.required" => "Informe o motivo do {$action}.",
            "stock_movement_reason.string" => "O motivo do {$action} deve ser um texto válido.",
            "stock_movement_reason.min" => "O motivo do {$action} deve ter pelo menos 3 caracteres.",
            "stock_movement_reason.max" => "O motivo do {$action} não pode ter mais de 500 caracteres.",
        ];
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function syncInstallments(SgcOrder $order, array $validated): void
    {
        $order->installments()->delete();

        $payment_method_id = $validated["payment_method_id"] ?? $order->payment_method_id;

        $payment_method = $payment_method_id
            ? SgcPaymentMethod::query()->find($payment_method_id)
            : null;

        if( $payment_method && !$payment_method->supports_installments ) {
            $this->createInstallment(
                $order,
                1,
                now()->toDateString(),
                (float) $order->total,
                $order->isConfirmed()
            );

            return;
        }

        $installments = $validated["installments"] ?? [];

        if( is_array($installments) && count($installments) > 0 ) {
            foreach( $installments as $installment ) {
                $this->createInstallment(
                    $order,
                    (int) $installment["installment_number"],
                    $installment["due_date"] ?? null,
                    (float) $installment["amount"],
                    false
                );
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

            $this->createInstallment(
                $order,
                $i,
                now()->addMonths($i)->toDateString(),
                $amount,
                false
            );
        }
    }

    private function createInstallment(
        SgcOrder $order,
        int $number,
        mixed $due_date,
        float $amount,
        bool $paid,
    ): void {
        $amount = round($amount, 2);

        $order->installments()->create([
            "installment_number" => $number,
            "due_date" => $due_date,
            "amount" => $amount,
            "paid_amount" => $paid ? $amount : 0,
            "paid_at" => $paid ? now() : null,
        ]);
    }
}
