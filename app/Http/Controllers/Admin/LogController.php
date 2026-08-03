<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Carbon\Exceptions\InvalidFormatException;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\Response;

class LogController extends Controller
{
    public function indexFiles()
    {
        $logs_dir = storage_path("logs");
        $files = [];

        foreach(scandir($logs_dir) as $entry) {
            if( $entry === "." || $entry === ".." ) continue;

            $path = $logs_dir . DIRECTORY_SEPARATOR . $entry;

            if( !is_file($path) ) continue;
            if( !str_ends_with($entry, ".log") ) continue;

            $files[] = [
                "name" => $entry,
                "size" => filesize($path),
                "modified_at" => Carbon::createFromTimestamp(filemtime($path))->toIso8601String(),
            ];
        }

        usort($files, fn ($a, $b) => strcmp($a["name"], $b["name"]));

        return response()->json([
            "files" => $files,
        ], Response::HTTP_OK);
    }

    public function showFile(string $filename)
    {
        $path = $this->resolveLogFilePath($filename);

        if( $path === null ) return response()->json([
            "message" => "Arquivo de log não encontrado.",
        ], Response::HTTP_NOT_FOUND);

        return response()->json([
            "name" => $filename,
            "content" => file_get_contents($path),
        ], Response::HTTP_OK);
    }

    public function showDaily(string $date)
    {
        try {
            $parsed = Carbon::createFromFormat("!Y-m-d", $date);
        } catch (InvalidFormatException) {
            return response()->json([
                "message" => "Data inválida. Use o formato YYYY-MM-DD.",
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if( $parsed->format("Y-m-d") !== $date ) return response()->json([
            "message" => "Data inválida. Use o formato YYYY-MM-DD.",
        ], Response::HTTP_UNPROCESSABLE_ENTITY);

        $relative_path = "logs/" . $parsed->format("Y/m/d") . ".log";
        $path = storage_path($relative_path);

        if( !is_file($path) ) return response()->json([
            "message" => "Arquivo de log não encontrado.",
        ], Response::HTTP_NOT_FOUND);

        return response()->json([
            "content" => file_get_contents($path),
        ], Response::HTTP_OK);
    }

    private function resolveLogFilePath(string $filename): ?string
    {
        if( !preg_match('/^[a-zA-Z0-9._-]+\.log$/', $filename) ) return null;

        $path = storage_path("logs/" . $filename);
        $logs_dir = realpath(storage_path("logs"));
        $resolved = realpath($path);

        if( $logs_dir === false || $resolved === false ) return null;

        if( !is_file($resolved) ) return null;
        if( dirname($resolved) !== $logs_dir ) return null;

        return $resolved;
    }
}
