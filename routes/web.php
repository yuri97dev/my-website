<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SpreadsheetFormatterController;

Route::inertia("/", "Home")->name("home");
Route::inertia("/tools", "Tools/Index")->name("tools");

Route::get("/tools/spreadsheet-formatter", [
    SpreadsheetFormatterController::class, "index"
])->name("tools.spreadsheet-formatter");
