<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Str;

class NotificationDelivery extends Model
{
    public const STATUS_PENDING = "pending";
    public const STATUS_SENT = "sent";
    public const STATUS_FAILED = "failed";

    protected $fillable = [
        "uuid",
        "channel",
        "status",
        "user_id",
        "recipient",
        "subject",
        "body",
        "payload",
        "notifiable_type",
        "notifiable_id",
        "scheduled_at",
        "sent_at",
        "failed_at",
        "error_message",
        "attempts",
    ];

    protected static function booted(): void
    {
        static::creating(function (NotificationDelivery $delivery) {
            if( empty($delivery->uuid) ) {
                $delivery->uuid = (string) Str::uuid();
            }
        });
    }

    protected function casts(): array
    {
        return [
            "payload" => "array",
            "scheduled_at" => "datetime",
            "sent_at" => "datetime",
            "failed_at" => "datetime",
            "attempts" => "integer",
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function notifiable(): MorphTo
    {
        return $this->morphTo();
    }
}
