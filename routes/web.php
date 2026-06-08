<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SpreadsheetFormatterController;

Route::inertia("/", "Home")->name("home");
Route::inertia("/tools", "Tools/Index")->name("tools");
Route::inertia("/login", "Login")->name("login");
Route::inertia("/register", "Register")->name("register");
Route::inertia("/terms-of-use", "TermsOfUse")->name("terms_of_use");
Route::inertia("/privacy-policy", "PrivacyPolicy")->name("privacy_policy");
Route::inertia("/forgot-password", "ForgotPassword")->name("forgot_password");
Route::inertia("/reset-password", "ResetPassword")->name("reset_password");

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
});
