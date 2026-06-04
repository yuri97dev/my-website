<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SpreadsheetFormatterController;

Route::post("/tools/spreadsheet-formatter/upload", [
    SpreadsheetFormatterController::class, "upload"
])
    ->middleware("throttle:5,1")
    ->name("tools.spreadsheet-formatter.upload");
