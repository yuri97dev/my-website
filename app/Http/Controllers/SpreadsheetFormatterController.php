<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

use App\Helpers\UploadLimit;
use Inertia\Inertia;
use App\Http\Requests\SpreadsheetFormatter\UploadSpreadsheetRequest;

class SpreadsheetFormatterController extends Controller
{
    public function index()
    {
        $meta = [
            "title"=> "Formatador de Planilhas",
            "description"=> "Limpe e padronize dados em planilhas CSV ou XLSX. Remova acentos, espaços e caracteres especiais, transforme maiúsculas/minúsculas, e exporte dados prontos para análise.",
            "og_title"=> "Formatador de Planilhas",
            "og_description"=> "Ferramenta para limpar, padronizar e converter planilhas. Corrija variações de texto e prepare dados mais consistentes para análise. Compatível com CSV e XLSX.",
            "og_image" => asset("/images/spreadsheet-formatter/thumbnail-placeholder.png"),
        ];

        $max_upload_size = UploadLimit::spreadsheetFormatter();

        return Inertia::render(
            "Tools/SpreadsheetFormatter",
            compact("meta", "max_upload_size")
        );
    }

    public function upload(UploadSpreadsheetRequest $request)
    {
        $file = $request->file("spreadsheet");

        $original_name = $file->getClientOriginalName();
        $original_extension = $file->getClientOriginalExtension();

        $uuid = Str::uuid()->toString();
        $new_name = "{$uuid}.{$original_extension}";

        $path = "uploads/" . date("Y/m/d");

        $saved_as = $file->storeAs($path, $new_name);

        return response()->json([
            "uuid"=> $uuid,
        ]);
    }
}
