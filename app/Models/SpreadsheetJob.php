<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Http\Request;

class SpreadsheetJob extends Model
{
    public const STATUS_QUEUED = "queued";
    public const STATUS_PROCESSING = "processing";
    public const STATUS_DONE = "done";
    public const STATUS_FAILED = "failed";
    public const STATUS_CANCELLED = "cancelled";

    protected $fillable = [
        "uuid",
        "user_id",
        "session_id",
        "original_name",
        "extension",
        "size_bytes",
        "saved_as",
        "status",
        "progress",
        "format_options",
        "result_path",
        "result_name",
        "error_message",
        "expires_at",
        "file_removed_at",
    ];

    protected function casts(): array
    {
        return [
            "format_options"=> "array",
            "expires_at"=> "datetime",
            "file_removed_at"=> "datetime",
            "size_bytes"=> "integer",
            "progress"=> "integer",
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeOwnedBy(Builder $query, Request $request): Builder
    {
        if( $request->user() ) {
            return $query->where("user_id", $request->user()->id);
        }

        return $query->where("session_id", $request->session()->getId());
    }

    public function scopeNotExpired(Builder $query): Builder
    {
        return $query->where("expires_at", ">", now());
    }

    public function isTerminal(): bool
    {
        return in_array($this->status, [
            self::STATUS_DONE,
            self::STATUS_FAILED,
            self::STATUS_CANCELLED,
        ], true);
    }

    public function toFrontendArray(): array
    {
        return [
            "uuid"=> $this->uuid,
            "original_name"=> $this->original_name,
            "size_bytes"=> $this->size_bytes,
            "status"=> $this->status,
            "progress"=> $this->progress,
            "download_url"=> $this->status === self::STATUS_DONE
                ? route("tools.spreadsheet-formatter.download", $this->uuid)
                : null,
        ];
    }
}
