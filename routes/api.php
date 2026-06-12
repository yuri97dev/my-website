<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AccountController;
use App\Http\Controllers\Admin\JobController;
use App\Http\Controllers\Admin\LogController;
use App\Http\Controllers\Admin\SessionController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Blog\PostCategoryController;
use App\Http\Controllers\Blog\PostController;
use App\Http\Controllers\Blog\PostTagController;
use App\Http\Controllers\SpreadsheetFormatterController;
use App\Models\Role;

Route::prefix("auth")->group(function() {
    Route::post("/login", [
        AuthController::class, "login"
    ])->name("api.auth.login");

    Route::post("/register", [
        AuthController::class, "register"
    ])->name("api.auth.register");

    Route::post("/logout", [
        AuthController::class, "logout"
    ])->name("api.auth.logout");
});

Route::prefix("account")->group(function() {
    Route::post("/forgot-password", [
        AccountController::class, "forgotPassword",
    ])
        ->name("api.account.forgot-password")
        ->middleware("throttle:5,1");

    Route::post("/reset-password", [
        AccountController::class, "resetPassword",
    ])
        ->name("api.account.reset-password")
        ->middleware("throttle:5,1");

    Route::middleware("auth")->group(function() {
        Route::post("/email/verification-notification", [
            AccountController::class, "sendEmailVerificationNotification",
        ])
            ->name("api.account.email.verification-notification")
            ->middleware("throttle:6,1");

        Route::put("/personal-data", [
            AccountController::class, "updatePersonalData",
        ])->name("api.account.personal-data.update");

        Route::put("/password", [
            AccountController::class, "updatePassword",
        ])->name("api.account.password.update");

        Route::get("/sessions", [
            AccountController::class, "sessions",
        ])->name("api.account.sessions.index");

        Route::delete("/sessions/{session}", [
            AccountController::class, "revokeSession",
        ])->name("api.account.sessions.destroy");

        Route::post("/destroy", [
            AccountController::class, "destroy",
        ])->name("api.account.destroy");
    });
});

Route::middleware(["auth", "role:" . Role::SYSTEM_ADMIN])
    ->group(function () {
        Route::get("/sessions", [
            SessionController::class, "index",
        ])->name("api.sessions.index");

        Route::get("/users", [
            UserController::class, "index",
        ])->name("api.users.index");

        Route::get("/jobs", [
            JobController::class, "index",
        ])->name("api.jobs.index");

        Route::get("/jobs/spreadsheet-formatter", [
            JobController::class, "spreadsheetFormatter",
        ])->name("api.jobs.spreadsheet-formatter");

        Route::get("/logs/{date}", [
            LogController::class, "show",
        ])->name("api.logs.show");
    });

Route::prefix("tools")->group(function() {
    Route::post("/spreadsheet-formatter/upload", [
        SpreadsheetFormatterController::class, "upload"
    ])
        ->name("api.tools.spreadsheet-formatter.upload")
        ->middleware("throttle:5,1");

    Route::get("/spreadsheet-formatter/files/status", [
        SpreadsheetFormatterController::class, "status"
    ])->name("api.tools.spreadsheet-formatter.files.status");

    Route::delete("/spreadsheet-formatter/files/{uuid}", [
        SpreadsheetFormatterController::class, "destroy"
    ])->name("api.tools.spreadsheet-formatter.files.destroy");
});

Route::prefix("blog/posts")->group(function () {
    Route::get("/", [
        PostController::class, "index",
    ])->name("api.blog.posts.index");

    Route::get("/categories", [
        PostCategoryController::class, "index",
    ])->name("api.blog.posts.categories.index");

    Route::get("/tags", [
        PostTagController::class, "index",
    ])->name("api.blog.posts.tags.index");

    Route::middleware(["auth", "role:" . Role::SYSTEM_ADMIN])->group(function () {
        Route::post("/", [
            PostController::class, "store",
        ])->name("api.blog.posts.store");

        Route::put("/{post}", [
            PostController::class, "update",
        ])->name("api.blog.posts.update");

        Route::delete("/{post}", [
            PostController::class, "destroy",
        ])->name("api.blog.posts.destroy");

        Route::post("/categories", [
            PostCategoryController::class, "store",
        ])->name("api.blog.posts.categories.store");

        Route::put("/categories/{postCategory}", [
            PostCategoryController::class, "update",
        ])->name("api.blog.posts.categories.update");

        Route::delete("/categories/{postCategory}", [
            PostCategoryController::class, "destroy",
        ])->name("api.blog.posts.categories.destroy");

        Route::post("/tags", [
            PostTagController::class, "store",
        ])->name("api.blog.posts.tags.store");

        Route::put("/tags/{postTag}", [
            PostTagController::class, "update",
        ])->name("api.blog.posts.tags.update");

        Route::delete("/tags/{postTag}", [
            PostTagController::class, "destroy",
        ])->name("api.blog.posts.tags.destroy");
    });
});
