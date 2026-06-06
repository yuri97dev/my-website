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
        $max_upload_size = UploadLimit::spreadsheetFormatter();

        return Inertia::render(
            "Tools/SpreadsheetFormatter",
            compact("max_upload_size")
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
