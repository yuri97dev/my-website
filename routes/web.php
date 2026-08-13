<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AccountController;
use App\Http\Controllers\SpreadsheetFormatterController;
use App\Http\Controllers\Blog\PostController;
use App\Http\Controllers\Blog\PostImageController;
use App\Http\Controllers\Blog\ScrapedArticleImageController;
use App\Models\Role;

Route::inertia("/", "Home")->name("home");
Route::inertia("/tools", "Tools/Index")->name("tools");
Route::inertia("/login", "Login")->name("login");
Route::inertia("/register", "Register")->name("register");
Route::inertia("/terms-of-use", "TermsOfUse")->name("terms_of_use");
Route::inertia("/privacy-policy", "PrivacyPolicy")->name("privacy_policy");
Route::inertia("/forgot-password", "ForgotPassword")->name("forgot_password");
Route::inertia("/reset-password", "ResetPassword")->name("reset_password");

Route::get("/email/verify/{id}/{hash}", [
    AccountController::class, "verifyEmail",
])
    ->middleware(["signed", "throttle:6,1"])
    ->name("verification.verify");

Route::middleware("auth")->group(function () {
    Route::inertia("/account", "Account")->name("account");
});

Route::prefix("tools")->group(function () {
    Route::get("/spreadsheet-formatter", [
        SpreadsheetFormatterController::class, "index"
    ])->name("tools.spreadsheet-formatter");

    Route::get("/spreadsheet-formatter/files", [
        SpreadsheetFormatterController::class, "files"
    ])->name("tools.spreadsheet-formatter.files");

    Route::get("/spreadsheet-formatter/files/{uuid}/download", [
        SpreadsheetFormatterController::class, "download"
    ])->name("tools.spreadsheet-formatter.download");

    Route::middleware("auth")->prefix("sgc")->group(function () {
        Route::inertia("/", "Tools/Sgc/Index")->name("tools.sgc");
        Route::inertia("/companies", "Tools/Sgc/Companies")->name("tools.sgc.companies");
        Route::inertia("/customers", "Tools/Sgc/Customers")->name("tools.sgc.customers");
        Route::inertia("/products", "Tools/Sgc/Products")->name("tools.sgc.products");
    });
});

Route::prefix("blog")->group(function () {
    Route::get("/images/{post_image}", [
        PostImageController::class, "show",
    ])->name("blog.images.show");

    Route::get("/sc-images/{scraped_article_image}", [
        ScrapedArticleImageController::class, "show",
    ])->name("blog.scraped-images.show");

    Route::get("/{category?}", [
        PostController::class, "index"
    ])->name("blog.index");

    Route::get("/{category}/{slug}", [
        PostController::class, "show"
    ])->name("blog.show");
});

Route::middleware(["auth", "role:" . Role::SYSTEM_ADMIN])
    ->prefix("admin")
    ->group(function () {
        Route::inertia("/blog/posts", "Admin/Blog/Posts")->name("admin.blog.posts");
        Route::inertia("/blog/article-sources", "Admin/Blog/ArticleSources")->name("admin.blog.article-sources");
        Route::inertia("/sessions", "Admin/Sessions")->name("admin.sessions");
        Route::inertia("/users", "Admin/Users")->name("admin.users");
        Route::inertia("/jobs", "Admin/Jobs")->name("admin.jobs");
        Route::inertia("/logs", "Admin/Logs")->name("admin.logs");
        Route::inertia("/ai-providers", "Admin/AiProviders")->name("admin.ai-providers");
        Route::inertia("/scrape-http-profiles", "Admin/ScrapeHttpProfiles")->name("admin.scrape-http-profiles");
    });
