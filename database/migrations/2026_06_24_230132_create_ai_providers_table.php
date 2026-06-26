<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create("ai_providers", function (Blueprint $table) {
            $table->id();
            $table->string("name", 100)->unique();
            $table->string("provider", 50);
            $table->text("api_key")->nullable();
            $table->boolean("is_enabled")->default(true);
            $table->timestamps();
        });

        Schema::create("ai_prompts", function (Blueprint $table) {
            $table->id();

            $table->foreignId("ai_provider_id")
                ->constrained("ai_providers")
                ->cascadeOnDelete();

            $table->string("key", 100);
            $table->string("model", 100);
            $table->longText("content");
            $table->timestamps();

            $table->unique(["ai_provider_id", "key"]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists("ai_prompts");
        Schema::dropIfExists("ai_providers");
    }
};
