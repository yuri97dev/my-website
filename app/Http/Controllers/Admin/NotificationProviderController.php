<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\NotificationProvider\StoreNotificationProviderRequest;
use App\Http\Requests\NotificationProvider\UpdateNotificationProviderRequest;
use App\Models\NotificationProvider;

class NotificationProviderController extends Controller
{
    public function index()
    {
        $notification_providers = NotificationProvider::query()
            ->select([
                "id",
                "key",
                "channel",
                "name",
                "is_enabled",
                "created_at",
                "updated_at",
            ])
            ->orderByDesc("id")
            ->get();

        return response()->json([
            "notification_providers" => $notification_providers,
        ], 200);
    }

    public function store(StoreNotificationProviderRequest $request)
    {
        $data = $request->validated();

        $notification_provider = NotificationProvider::create([
            "key" => $data["key"],
            "channel" => $data["channel"],
            "name" => $data["name"],
            "config" => $data["config"],
            "is_enabled" => $data["is_enabled"] ?? true,
        ]);

        return response()->json([
            "notification_provider" => $notification_provider,
        ], 201);
    }

    public function update(
        UpdateNotificationProviderRequest $request,
        NotificationProvider $notification_provider,
    ) {
        $data = $request->validated();

        if( array_key_exists("config", $data) ) {
            $config = $notification_provider->config ?? [];

            foreach( $data["config"] as $key => $value ) {
                if( filled($value) ) {
                    $config[$key] = $value;
                }
            }

            $data["config"] = $config;
        }

        $notification_provider->update($data);

        return response()->json([
            "notification_provider" => $notification_provider->fresh(),
        ], 200);
    }
}
