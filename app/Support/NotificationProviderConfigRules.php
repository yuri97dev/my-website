<?php

namespace App\Support;

use App\Models\NotificationProvider;
use Illuminate\Validation\Rule;

class NotificationProviderConfigRules
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public static function forChannel(string $channel, bool $creating = true): array
    {
        return match ($channel) {
            NotificationProvider::CHANNEL_DISCORD => [
                "config" => [$creating ? "required" : "sometimes", "array"],
                "config.webhook_url" => $creating
                    ? ["required", "url", "max:500"]
                    : ["sometimes", "nullable", "url", "max:500"],
            ],
            default => [
                "config" => [$creating ? "required" : "sometimes", "array"],
            ],
        };
    }

    /**
     * @return array<int, mixed>
     */
    public static function channel(bool $updating = false): array
    {
        $rules = [
            "string",
            "max:20",
            Rule::in(NotificationProvider::channels()),
        ];

        if( $updating ) {
            array_unshift($rules, "sometimes", "required");
        } else {
            array_unshift($rules, "required");
        }

        return $rules;
    }
}
