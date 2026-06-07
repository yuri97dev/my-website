<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

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
