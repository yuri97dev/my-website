<?php

namespace App\Models;

use App\Casts\EncryptedString;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class License extends Model
{
    public const STATUS_PENDING = "pending";
    public const STATUS_ACTIVE = "active";
    public const STATUS_SUSPENDED = "suspended";
    public const STATUS_REVOKED = "revoked";

    protected $fillable = [
        "uuid",
        "user_id",
        "label",
        "observations",
        "status",
        "expires_at",
        "fingerprint",
        "last_validated_at",
        "public_key",
        "private_key_encrypted",
    ];

    protected $hidden = [
        "private_key_encrypted",
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if( empty($model->uuid) ) {
                $model->uuid = Str::uuid()->toString();
            }
        });
    }

    protected function casts(): array
    {
        return [
            "expires_at"=> "date",
            "last_validated_at"=> "datetime",
            "private_key_encrypted"=> EncryptedString::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeAccessibleBy(Builder $query, User $user): Builder
    {
        return $query->where("user_id", $user->id);
    }

    public function isAccessibleBy(User $user): bool
    {
        return $this->user_id === $user->id;
    }
}
