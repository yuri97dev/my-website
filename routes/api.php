<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\SpreadsheetFormatterController;

Route::group(["prefix" => "auth"], function () {
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

Route::group(["prefix" => "tools"], function () {
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
