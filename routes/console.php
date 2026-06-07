<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Actions\RemoveExpiredSpreadsheetFiles;

Artisan::command("inspire", function () {
    $this->comment(Inspiring::quote());
})->purpose("Display an inspiring quote");

Schedule::call(new RemoveExpiredSpreadsheetFiles)
    ->name("remove-expired-spreadsheet-files")
    ->everyMinute()
    ->withoutOverlapping();
