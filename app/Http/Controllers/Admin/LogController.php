<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Carbon\Exceptions\InvalidFormatException;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\Response;

class LogController extends Controller
{
    public function show(string $date)
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

        $filename = "laravel-{$parsed->format("Y-m-d")}.log";
        $path = storage_path("logs/{$filename}");

        if( !is_file($path) ) return response()->json([
            "message" => "Arquivo de log não encontrado.",
        ], Response::HTTP_NOT_FOUND);

        return response()->json([
            "date" => $parsed->format("Y-m-d"),
            "name" => $filename,
            "content" => file_get_contents($path),
        ], Response::HTTP_OK);
    }
}
