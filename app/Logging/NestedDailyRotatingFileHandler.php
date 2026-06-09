<?php

namespace App\Logging;

use Monolog\Handler\RotatingFileHandler;

class NestedDailyRotatingFileHandler extends RotatingFileHandler
{
    protected function getTimedFilename(): string
    {
        $relative_path = "logs/" . now()->format("Y/m/d") . ".log";

        $path = storage_path($relative_path);
        $directory = dirname($path);

        if( !is_dir($directory) ) {
            mkdir($directory, 0755, true);
        }

        return $path;
    }
}
