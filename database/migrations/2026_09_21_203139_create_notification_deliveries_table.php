<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create("notification_deliveries", function (Blueprint $table) {
            $table->id();
            $table->uuid("uuid")->unique();

            $table->string("channel", 20);
            $table->string("status", 20)->default("pending");

            $table->foreignId("user_id")
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->string("recipient")->nullable();
            $table->string("subject")->nullable();
            $table->text("body")->nullable();
            $table->json("payload")->nullable();

            $table->nullableMorphs("notifiable");

            $table->timestamp("scheduled_at")->nullable()->index();
            $table->timestamp("sent_at")->nullable();
            $table->timestamp("failed_at")->nullable();
            $table->text("error_message")->nullable();
            $table->unsignedTinyInteger("attempts")->default(0);

            $table->timestamps();

            $table->index(["status", "scheduled_at"]);
            $table->index(["user_id", "created_at"]);
            $table->index(["channel", "status"]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists("notification_deliveries");
    }
};
