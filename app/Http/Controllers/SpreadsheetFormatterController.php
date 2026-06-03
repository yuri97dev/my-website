<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Helpers\UploadLimit;
use Inertia\Inertia;

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
}
