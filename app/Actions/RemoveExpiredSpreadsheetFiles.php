<?php

namespace App\Actions;

use Illuminate\Support\Facades\Log;
use App\Models\SpreadsheetJob;

class RemoveExpiredSpreadsheetFiles
{
    public function __invoke(): void
    {
        $records = SpreadsheetJob::query()
            ->where("expires_at", "<=", now())
            ->whereNull("file_removed_at")
            ->limit(50)
            ->get();

        foreach ($records as $record) {
            $this->removeFiles($record);
        }
    }

    private function removeFiles(SpreadsheetJob $record): void
    {
        try {
            $this->tryUnlink($record->saved_as);
            $this->tryUnlink($record->result_path);

            $record->update(["file_removed_at" => now()]);
        } catch (\Throwable $e) {
            Log::error("RemoveExpiredSpreadsheetFiles: failed to remove files for job {$record->uuid}: {$e->getMessage()}");
        }
    }

    private function tryUnlink(?string $relative_path): void
    {
        if( empty($relative_path) ) {
            return;
        }

        $absolute_path = storage_path("app/private/{$relative_path}");

        if( is_file($absolute_path) ) {
            unlink($absolute_path);
        }
    }
}
