<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AccountController;
use App\Http\Controllers\Admin\SessionController;
use App\Http\Controllers\Admin\UserController;
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
