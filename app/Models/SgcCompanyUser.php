<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SgcCompanyUser extends Model
{
    protected $table = "sgc_company_user";

    protected $fillable = [
        "company_id",
        "user_id",
        "role",
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(SgcCompany::class, "company_id");
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, "user_id");
    }

    public function isOwner(): bool
    {
        return $this->company?->owner_id === $this->user_id;
    }
}
