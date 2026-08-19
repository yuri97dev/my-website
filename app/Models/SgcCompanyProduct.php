<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;

class SgcCompanyProduct extends Model
{
    protected $table = "sgc_company_product";

    /**
     * @var array{
     *     type?: string,
     *     reason?: string|null,
     *     reference?: Model|null,
     *     user_id?: int|null,
     *     quantity_before?: float|null
     * }
     */
    protected array $stockMovementMeta = [];

    protected $fillable = [
        "company_id",
        "product_id",
        "unit_of_measure_id",
        "sale_price",
        "cost_price",
        "stock_quantity",
        "serial_number",
        "currency",
        "notes",
    ];

    protected function casts(): array
    {
        return [
            "sale_price" => "decimal:2",
            "cost_price" => "decimal:2",
            "stock_quantity" => "decimal:3",
        ];
    }

    protected static function booted(): void
    {
        static::created(function (self $model) {
            $model->recordStockMovement(
                0,
                (float) $model->stock_quantity,
                $model->stockMovementMeta["type"] ?? SgcCompanyProductStockMovement::TYPE_ENTRY,
            );
        });

        static::updating(function (self $model) {
            if( !$model->isDirty("stock_quantity") ) {
                return;
            }

            $model->stockMovementMeta["quantity_before"] = round(
                (float) $model->getOriginal("stock_quantity"),
                3
            );
        });

        static::updated(function (self $model) {
            if( !array_key_exists("quantity_before", $model->stockMovementMeta) ) {
                return;
            }

            $model->recordStockMovement(
                (float) $model->stockMovementMeta["quantity_before"],
                (float) $model->stock_quantity,
                $model->stockMovementMeta["type"] ?? SgcCompanyProductStockMovement::TYPE_ADJUSTMENT,
            );
        });

        static::deleting(function (self $model) {
            $model->recordStockMovement(
                (float) $model->stock_quantity,
                0,
                $model->stockMovementMeta["type"] ?? SgcCompanyProductStockMovement::TYPE_DELETION,
            );
        });
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(SgcCompany::class, "company_id");
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(SgcProduct::class, "product_id");
    }

    public function unitOfMeasure(): BelongsTo
    {
        return $this->belongsTo(SgcUnitOfMeasure::class, "unit_of_measure_id");
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(SgcCompanyProductStockMovement::class, "company_product_id");
    }

    public function setStockMovementMeta(
        string $type,
        ?string $reason = null,
        ?Model $reference = null,
        ?int $user_id = null,
    ): static {
        $this->stockMovementMeta = [
            ...$this->stockMovementMeta,
            "type" => $type,
            "reason" => $reason,
            "reference" => $reference,
            "user_id" => $user_id,
        ];

        return $this;
    }

    public static function quantitiesDiffer(mixed $left, mixed $right): bool
    {
        return round((float) $left, 3) !== round((float) $right, 3);
    }

    private function recordStockMovement(float $quantity_before, float $quantity_after, string $type): void
    {
        $quantity_before = round($quantity_before, 3);
        $quantity_after = round($quantity_after, 3);

        $reason = $this->stockMovementMeta["reason"] ?? null;
        $reference = $this->stockMovementMeta["reference"] ?? null;

        if( !self::quantitiesDiffer($quantity_before, $quantity_after) && $type === SgcCompanyProductStockMovement::TYPE_ENTRY ) {
            $this->stockMovementMeta = [];

            return;
        }

        if(
            !self::quantitiesDiffer($quantity_before, $quantity_after)
            && $type !== SgcCompanyProductStockMovement::TYPE_DELETION
        ) {
            $this->stockMovementMeta = [];

            return;
        }

        $this->stockMovements()->create([
            "company_id" => $this->company_id,
            "product_id" => $this->product_id,
            "user_id" => $this->stockMovementMeta["user_id"] ?? Auth::id(),
            "reference_type" => $reference instanceof Model ? $reference->getMorphClass() : null,
            "reference_id" => $reference instanceof Model ? $reference->getKey() : null,
            "type" => $type,
            "quantity_before" => $quantity_before,
            "quantity_after" => $quantity_after,
            "quantity_delta" => round($quantity_after - $quantity_before, 3),
            "reason" => $reason,
        ]);

        $this->stockMovementMeta = [];
    }
}
