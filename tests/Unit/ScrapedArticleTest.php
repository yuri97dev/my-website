<?php

use App\Models\ScrapedArticle;
use App\Models\User;

it("nao trata artigo sem processador como reservado pelo ajudante", function () {
    $article = new ScrapedArticle(["processing_by" => null]);
    expect($article->isProcessedByAssistant())->toBeFalse();
});

it("reconhece artigo reservado pelo ajudante", function () {
    $article = new ScrapedArticle(["processing_by" => User::ASSISTANT_ID]);
    expect($article->isProcessedByAssistant())->toBeTrue();
});

it("nao trata artigo reservado por usuario como reservado pelo ajudante", function () {
    $article = new ScrapedArticle(["processing_by" => 7]);
    expect($article->isProcessedByAssistant())->toBeFalse();
});
