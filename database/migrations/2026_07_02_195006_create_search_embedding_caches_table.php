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
        Schema::create("search_embedding_caches", function (Blueprint $table) {
            $table->id();
            $table->text("normalized_query");
            $table->string("embedding_model", 100);
            $table->timestamps();

            $table->unique(["normalized_query", "embedding_model"]);
        });

        DB::statement("ALTER TABLE search_embedding_caches ADD COLUMN embedding vector(" . self::EMBEDDING_DIMENSION . ")");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists("search_embedding_caches");
    }
};
