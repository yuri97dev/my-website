<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class SgcProduct extends Model
{
    use SoftDeletes;

    protected $fillable = [
        "brand_id",
        "product_category_id",
        "name",
        "technical_specifications",
    ];

    public function brand(): BelongsTo
    {
        return $this->belongsTo(SgcBrand::class, "brand_id");
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(SgcProductCategory::class, "product_category_id");
    }

    public function companies(): BelongsToMany
    {
        return $this->belongsToMany(SgcCompany::class, "sgc_company_product", "product_id", "company_id")
            ->withPivot([
                "notes",
                "sale_price",
                "cost_price",
                "currency",
            ])
            ->withTimestamps();
    }
}
