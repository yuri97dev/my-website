<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create("article_source_ai_prompts", function (Blueprint $table) {
            $table->id();

            $table->foreignId("article_source_id")
                ->constrained("article_sources")
                ->cascadeOnDelete();

            $table->foreignId("ai_prompt_id")
                ->constrained("ai_prompts")
                ->cascadeOnDelete();

            $table->unsignedSmallInteger("position")->default(1);
            $table->timestamps();

            $table->unique(["article_source_id", "ai_prompt_id"]);
        });

        $aiPromptId = DB::table("ai_prompts")
            ->where("key", "post-translation")
            ->orderBy("id")
            ->value("id");

        if( !$aiPromptId ) {
            return;
        }

        $articleSourceIds = DB::table("article_sources")->pluck("id");
        $now = now();

        $rows = $articleSourceIds->map(fn ($articleSourceId) => [
            "article_source_id" => $articleSourceId,
            "ai_prompt_id" => $aiPromptId,
            "position" => 1,
            "created_at" => $now,
            "updated_at" => $now,
        ])->all();

        if( !empty($rows) ) {
            DB::table("article_source_ai_prompts")->insert($rows);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists("article_source_ai_prompts");
    }
};
