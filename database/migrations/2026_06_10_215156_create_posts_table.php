<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const EMBEDDING_DIMENSION = 1024;

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("CREATE EXTENSION IF NOT EXISTS vector");

        Schema::create("post_categories", function (Blueprint $table) {
            $table->id();
            $table->string("slug")->unique();
            $table->string("name");
            $table->timestamps();
        });

        Schema::create("posts", function (Blueprint $table) {
            $table->id();

            $table->foreignId("author_id")
                ->nullable()
                ->constrained("users")
                ->nullOnDelete();

            $table->foreignId("post_category_id")
                ->nullable()
                ->constrained("post_categories")
                ->nullOnDelete();

            $table->string("slug")->unique();
            $table->string("title", 200);
            $table->enum("status", ["draft", "published", "archived"])->default("draft");
            $table->string("excerpt", 500)->nullable();
            $table->longText("content");
            $table->timestamp("published_at")->nullable()->index();
            $table->string("meta_description", 160)->nullable();
            $table->string("thumbnail_path", 500)->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index(["post_category_id", "published_at"]);
            $table->index(["status", "published_at"]);
        });

        Schema::create("post_chunks", function (Blueprint $table) {
            $table->id();

            $table->foreignId("post_id")
                ->constrained()
                ->cascadeOnDelete();

            $table->unsignedSmallInteger("chunk_index");
            $table->text("content");
            $table->unsignedSmallInteger("token_count")->nullable();
            $table->string("embedding_model", 100);
            $table->timestamps();

            $table->unique(["post_id", "chunk_index"]);
        });

        DB::statement("ALTER TABLE post_chunks ADD CONSTRAINT post_chunks_chunk_index_check CHECK (chunk_index >= 0)");

        DB::statement("ALTER TABLE post_chunks ADD CONSTRAINT post_chunks_token_count_check CHECK (token_count IS NULL OR token_count > 0)");

        DB::statement("ALTER TABLE post_chunks ADD COLUMN embedding vector(" . self::EMBEDDING_DIMENSION . ")");

        DB::statement("CREATE INDEX post_chunks_embedding_idx ON post_chunks USING hnsw (embedding vector_cosine_ops) WHERE embedding IS NOT NULL");

        Schema::create("post_tags", function (Blueprint $table) {
            $table->id();
            $table->string("slug")->unique();
            $table->string("name");
            $table->timestamps();
        });

        Schema::create("post_tag", function (Blueprint $table) {
            $table->foreignId("post_id")
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId("post_tag_id")
                ->constrained("post_tags")
                ->cascadeOnDelete();

            $table->primary(["post_id", "post_tag_id"]);
        });

        Schema::create("post_images", function (Blueprint $table) {
            $table->id();

            $table->foreignId("post_id")
                ->constrained()
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
        Schema::dropIfExists("post_tag");
        Schema::dropIfExists("post_tags");
        Schema::dropIfExists("post_images");
        Schema::dropIfExists("post_chunks");
        Schema::dropIfExists("posts");
        Schema::dropIfExists("post_categories");
    }
};
