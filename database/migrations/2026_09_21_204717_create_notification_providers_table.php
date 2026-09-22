<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create("notification_providers", function (Blueprint $table) {
            $table->id();

            $table->string("key", 100);
            $table->string("channel", 20);
            $table->string("name", 100);
            $table->text("config");
            $table->boolean("is_enabled")->default(true);

            $table->timestamps();

            $table->unique(["channel", "key"]);
            $table->index(["channel", "is_enabled"]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists("notification_providers");
    }
};
