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
        Schema::table("scraped_articles", function (Blueprint $table) {
            $table->unsignedTinyInteger("translation_attempts")->default(0)->after("translation_rejected_at");
        });

        Schema::create("scraped_article_translation_caches", function (Blueprint $table) {
            $table->id();

            $table->foreignId("scraped_article_id")
                ->constrained("scraped_articles")
                ->cascadeOnDelete();

            $table->foreignId("ai_prompt_id")
                ->constrained("ai_prompts")
                ->cascadeOnDelete();

            $table->longText("content");
            $table->timestamps();

            $table->unique(["scraped_article_id", "ai_prompt_id"]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists("scraped_article_translation_caches");

        Schema::table("scraped_articles", function (Blueprint $table) {
            $table->dropColumn("translation_attempts");
        });
    }
};
