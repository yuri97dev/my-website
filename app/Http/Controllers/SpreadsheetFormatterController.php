<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

use App\Helpers\UploadLimit;
use Inertia\Inertia;
use App\Http\Requests\SpreadsheetFormatter\UploadSpreadsheetRequest;

class SpreadsheetFormatterController extends Controller
{
    private $files_session_key = "spreadsheet_formatter.files";

    public function index()
    {
        $max_upload_size = UploadLimit::spreadsheetFormatter();

        return Inertia::render(
            "Tools/SpreadsheetFormatter/Index",
            compact("max_upload_size")
        );
    }

    public function files(Request $request)
    {
        $files = $request->session()
            ->get($this->files_session_key, []);

        $files_path = storage_path("app/private/");

        $files = array_filter($files, fn($file) => file_exists(
            $files_path . $file["saved_as"]
        ));

        $files = array_map(fn($file) => [
            "uuid"=> $file["uuid"],
            "original_name"=> $file["original_name"],
        ], $files);

        $files = array_values($files);

        return Inertia::render(
            "Tools/SpreadsheetFormatter/Files",
            compact("files")
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

        $files = $request->session()
            ->get($this->files_session_key, []);

        $files[] = [
            "uuid"=> $uuid,
            "original_name"=> $original_name,
            "saved_as"=> $saved_as,
        ];

        $request->session()
            ->put($this->files_session_key, $files);

        return response()->json([
            "uuid"=> $uuid,
        ]);
    }
}
