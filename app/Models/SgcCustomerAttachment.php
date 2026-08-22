<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class SgcCustomerAttachment extends Model
{
    public const TYPE_IDENTITY_DOCUMENT = "identity_document";
    public const TYPE_CONTRACT = "contract";
    public const TYPE_OTHER = "other";

    protected $fillable = [
        "company_customer_id",
        "type",
        "original_name",
        "path",
        "mime_type",
        "size_bytes",
    ];

    /**
     * @return list<string>
     */
    public static function types(): array
    {
        return [
            self::TYPE_IDENTITY_DOCUMENT,
            self::TYPE_CONTRACT,
            self::TYPE_OTHER,
        ];
    }

    public function companyCustomer(): BelongsTo
    {
        return $this->belongsTo(SgcCompanyCustomer::class, "company_customer_id");
    }

    public function isAccessibleBy(User $user): bool
    {
        return $this->companyCustomer?->isAccessibleBy($user) ?? false;
    }

    public function deleteStoredFile(): void
    {
        if( $this->path ) {
            Storage::disk("local")->delete($this->path);
        }
    }
}
