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
        Schema::create("article_sources", function (Blueprint $table) {
            $table->id();
            $table->enum("driver", ["rss", "browser"]);
            $table->string("name", 100)->unique();
            $table->string("locale", 10)->default("en");
            $table->string("http_method", 10)->default("GET");
            $table->string("list_url")->unique();
            $table->string("list_item_selector");
            $table->string("item_title_selector");
            $table->string("item_link_selector");
            $table->string("content_selector");
            $table->boolean("is_enabled")->default(true);
            $table->timestamps();
        });

        Schema::create("scraped_articles", function (Blueprint $table) {
            $table->id();

            $table->foreignId("article_source_id")
                ->constrained("article_sources")
                ->cascadeOnDelete();

            $table->foreignId("post_id")
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->string("url", 1000)->unique();
            $table->string("title", 500);
            $table->longText("content");
            $table->string("translated_title", 500)->nullable();
            $table->longText("translated_content")->nullable();
            $table->timestamp("translation_rejected_at")->nullable();
            $table->boolean("is_enabled")->default(true);
            $table->timestamps();
        });

        Schema::create("scraped_article_images", function (Blueprint $table) {
            $table->uuid("id")->primary();

            $table->foreignId("scraped_article_id")
                ->constrained("scraped_articles")
                ->cascadeOnDelete();

            $table->string("path", 500);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists("scraped_article_images");
        Schema::dropIfExists("scraped_articles");
        Schema::dropIfExists("article_sources");
    }
};
