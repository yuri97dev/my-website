<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\NotificationDelivery\ResendNotificationDeliveryRequest;
use App\Jobs\ResendNotificationDeliveryJob;
use App\Models\NotificationDelivery;
use Illuminate\Http\Request;

class NotificationDeliveryController extends Controller
{
    public function index(Request $request)
    {
        $per_page = min($request->integer("per_page", 15), 100);

        $paginator = NotificationDelivery::query()
            ->select([
                "id",
                "channel",
                "status",
                "subject",
                "sent_at",
                "failed_at",
                "attempts",
                "created_at",
            ])
            ->when($request->filled("id"), function ($query) use ($request) {
                $query->where("id", $request->input("id"));
            })
            ->when($request->filled("subject"), function ($query) use ($request) {
                $query->where("subject", "like", "%{$request->input("subject")}%");
            })
            ->when($request->filled("status"), function ($query) use ($request) {
                $query->where("status", $request->input("status"));
            })
            ->when($request->filled("channel"), function ($query) use ($request) {
                $query->where("channel", $request->input("channel"));
            })
            ->orderByDesc("id")
            ->paginate($per_page);

        return response()->json([
            "current_page" => $paginator->currentPage(),
            "last_page" => $paginator->lastPage(),
            "per_page" => $paginator->perPage(),
            "total" => $paginator->total(),
            "notification_deliveries" => $paginator->items(),
        ], 200);
    }

    public function show(NotificationDelivery $notification_delivery)
    {
        return response()->json([
            "notification_delivery" => $notification_delivery,
        ], 200);
    }

    public function resend(
        ResendNotificationDeliveryRequest $request,
        NotificationDelivery $notification_delivery,
    ) {
        ResendNotificationDeliveryJob::dispatch(
            $notification_delivery->id,
            $request->validated("subject"),
            $request->validated("body"),
        );

        return response()->json([
            "message" => "Reenvio enfileirado com sucesso.",
        ], 202);
    }
}
