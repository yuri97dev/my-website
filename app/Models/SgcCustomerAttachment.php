<?php

namespace App\Models;

use App\Concerns\OptimizesStoredImage;
use App\DataTransferObjects\OptimizationResult;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class SgcCustomerAttachment extends Model
{
    use OptimizesStoredImage;

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
        "original_size_bytes",
        "optimization_attempts",
        "optimized_at",
    ];

    protected function casts(): array
    {
        return [
            "optimized_at" => "datetime",
        ];
    }

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
            Storage::disk("local")->delete($this->storagePath());
        }
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopePendingOptimization(Builder $query): Builder
    {
        return $query
            ->whereNull("optimized_at")
            ->where("optimization_attempts", "<", self::MAX_OPTIMIZATION_ATTEMPTS)
            ->where("mime_type", "like", "image/%");
    }

    protected function usesLeadingSlashInPath(): bool
    {
        return false;
    }

    /**
     * @return array<string, mixed>
     */
    protected function extraFieldsAfterOptimization(?OptimizationResult $result): array
    {
        if( $result === null || !$result->converted ) {
            return [];
        }

        return [
            "mime_type" => "image/webp",
            "original_name" => $this->replaceOriginalNameExtension("webp"),
        ];
    }

    private function replaceOriginalNameExtension(string $extension): string
    {
        $original_name = (string) $this->original_name;
        $basename = pathinfo($original_name, PATHINFO_FILENAME);

        if( $basename === "" ) {
            return "arquivo.{$extension}";
        }

        return "{$basename}.{$extension}";
    }
}
