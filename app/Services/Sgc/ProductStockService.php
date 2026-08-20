<?php

namespace App\Services\Sgc;

use App\Models\SgcCompanyProduct;
use App\Models\SgcCompanyProductStockMovement;
use App\Models\SgcOrder;
use App\Models\SgcProduct;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class ProductStockService
{
    /**
     * @param  array<int, array<string, mixed>>  $items
     */
    public function deductForSale(SgcOrder $order, array $items, ?User $user = null): void
    {
        $this->adjust(
            (int) $order->company_id,
            $items,
            -1,
            SgcCompanyProductStockMovement::TYPE_SALE,
            null,
            $order,
            $user,
        );
    }

    public function restoreForCancellation(SgcOrder $order, string $reason, ?User $user = null): void
    {
        $this->restore(
            $order,
            SgcCompanyProductStockMovement::TYPE_CANCELLATION,
            $reason,
            $user,
        );
    }

    public function restoreForDeletion(SgcOrder $order, string $reason, ?User $user = null): void
    {
        $this->restore(
            $order,
            SgcCompanyProductStockMovement::TYPE_DELETION,
            $reason,
            $user,
        );
    }

    private function restore(
        SgcOrder $order,
        string $type,
        string $reason,
        ?User $user,
    ): void {
        $items = $order->items()->get([
            "product_id",
            "company_product_id",
            "quantity",
        ])->map(function ($item) {
            return [
                "product_id" => $item->product_id,
                "company_product_id" => $item->company_product_id,
                "quantity" => $item->quantity,
            ];
        })->all();

        $this->adjust(
            (int) $order->company_id,
            $items,
            1,
            $type,
            $reason,
            $order,
            $user,
        );
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     */
    private function adjust(
        int $company_id,
        array $items,
        int $multiplier,
        string $type,
        ?string $reason,
        SgcOrder $order,
        ?User $user,
    ): void {
        $quantities = [];

        foreach( $items as $item ) {
            $product_id = $item["product_id"] ?? null;
            $company_product_id = $item["company_product_id"] ?? null;

            if( !$product_id ) {
                continue;
            }

            $key = $company_product_id
                ? "cp:{$company_product_id}"
                : "p:{$product_id}";

            $quantities[$key] = [
                "product_id" => $product_id,
                "company_product_id" => $company_product_id,
                "quantity" => ($quantities[$key]["quantity"] ?? 0)
                    + (float) ($item["quantity"] ?? 0),
            ];
        }

        foreach( $quantities as $data ) {
            $delta = round($data["quantity"] * $multiplier, 3);

            if( $delta == 0 ) {
                continue;
            }

            if( $data["company_product_id"] ) {
                $pivot = SgcCompanyProduct::query()
                    ->where("id", $data["company_product_id"])
                    ->where("company_id", $company_id)
                    ->where("product_id", $data["product_id"])
                    ->lockForUpdate()
                    ->first();

                if( !$pivot ) {
                    throw ValidationException::withMessages([
                        "items" => "Unidade do produto não vinculada a esta empresa.",
                    ]);
                }

                if( $delta < 0 ) {
                    $this->consumeFromPivot(
                        $pivot,
                        abs($delta),
                        (int) $data["product_id"],
                        $type,
                        $reason,
                        $order,
                        $user,
                    );

                    continue;
                }

                $this->applyDelta($pivot, $delta, $type, $reason, $order, $user);

                continue;
            }

            $pivots = SgcCompanyProduct::query()
                ->where("company_id", $company_id)
                ->where("product_id", $data["product_id"])
                ->orderBy("id")
                ->lockForUpdate()
                ->get();

            if( $pivots->isEmpty() ) {
                throw ValidationException::withMessages([
                    "items" => "Produto não vinculado a esta empresa.",
                ]);
            }

            if( $delta < 0 ) {
                $this->consumeStock($pivots, abs($delta), (int) $data["product_id"], $type, $reason, $order, $user);

                continue;
            }

            $pivot = $pivots->first();

            $this->applyDelta($pivot, $delta, $type, $reason, $order, $user);
        }
    }

    private function consumeFromPivot(
        SgcCompanyProduct $pivot,
        float $needed,
        int $product_id,
        string $type,
        ?string $reason,
        SgcOrder $order,
        ?User $user,
    ): void {
        $available = round((float) $pivot->stock_quantity, 3);
        $needed = round($needed, 3);

        if( $needed > $available ) {
            $name = SgcProduct::query()->whereKey($product_id)->value("name");

            throw ValidationException::withMessages([
                "items" => $name
                    ? "Estoque insuficiente para o produto {$name}."
                    : "Estoque insuficiente para um dos produtos do pedido.",
            ]);
        }

        $this->applyDelta($pivot, -$needed, $type, $reason, $order, $user);
    }

    /**
     * @param  Collection<int, SgcCompanyProduct>  $pivots
     */
    private function consumeStock(
        Collection $pivots,
        float $needed,
        int $product_id,
        string $type,
        ?string $reason,
        SgcOrder $order,
        ?User $user,
    ): void {
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

            $this->applyDelta($pivot, -$take, $type, $reason, $order, $user);

            $remaining = round($remaining - $take, 3);
        }
    }

    private function applyDelta(
        SgcCompanyProduct $pivot,
        float $delta,
        string $type,
        ?string $reason,
        SgcOrder $order,
        ?User $user,
    ): void {
        $pivot->setStockMovementMeta(
            $type,
            $reason,
            $order,
            $user?->id,
        );

        $pivot->stock_quantity = round((float) $pivot->stock_quantity + $delta, 3);
        $pivot->save();
    }
}
