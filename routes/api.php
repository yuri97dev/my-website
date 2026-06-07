<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AccountController;
use App\Http\Controllers\SpreadsheetFormatterController;

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

Route::prefix("account")
    ->middleware("auth")
    ->group(function() {
        Route::put("/personal-data", [
            AccountController::class, "updatePersonalData"
        ])->name("api.account.personal-data.update");

        Route::put("/password", [
            AccountController::class, "updatePassword"
        ])->name("api.account.password.update");

        Route::get("/sessions", [
            AccountController::class, "sessions"
        ])->name("api.account.sessions.index");

        Route::delete("/sessions/{session}", [
            AccountController::class, "revokeSession"
        ])->name("api.account.sessions.destroy");

        Route::post("/destroy", [
            AccountController::class, "destroy"
        ])->name("api.account.destroy");
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
