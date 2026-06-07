<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SpreadsheetFormatterController;

Route::inertia("/", "Home")->name("home");
Route::inertia("/tools", "Tools/Index")->name("tools");
Route::inertia("/login", "Login")->name("login");
Route::inertia("/register", "Register")->name("register");
Route::inertia("/terms-of-use", "TermsOfUse")->name("terms_of_use");
Route::inertia("/privacy-policy", "PrivacyPolicy")->name("privacy_policy");

Route::group(["prefix" => "tools"], function () {
    Route::get("/spreadsheet-formatter", [
        SpreadsheetFormatterController::class, "index"
    ])->name("tools.spreadsheet-formatter");

    Route::post("/spreadsheet-formatter/upload", [
        SpreadsheetFormatterController::class, "upload"
    ])
        ->name("tools.spreadsheet-formatter.upload")
        ->middleware("throttle:5,1");

    Route::get("/spreadsheet-formatter/files", [
        SpreadsheetFormatterController::class, "files"
    ])->name("tools.spreadsheet-formatter.files");

    Route::get("/spreadsheet-formatter/files/status", [
        SpreadsheetFormatterController::class, "status"
    ])->name("tools.spreadsheet-formatter.files.status");

    Route::get("/spreadsheet-formatter/files/{uuid}/download", [
        SpreadsheetFormatterController::class, "download"
    ])->name("tools.spreadsheet-formatter.download");

    Route::delete("/spreadsheet-formatter/files/{uuid}", [
        SpreadsheetFormatterController::class, "destroy"
    ])->name("tools.spreadsheet-formatter.files.destroy");
});
