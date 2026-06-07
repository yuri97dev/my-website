<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

use App\Helpers\UploadLimit;
use Inertia\Inertia;
use App\Http\Requests\SpreadsheetFormatter\UploadSpreadsheetRequest;
use App\Jobs\ProcessSpreadsheetJob;
use App\Models\SpreadsheetJob;
use Symfony\Component\HttpFoundation\Response;

class SpreadsheetFormatterController extends Controller
{
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
        return Inertia::render("Tools/SpreadsheetFormatter/Files");
    }

    public function status(Request $request)
    {
        $files = SpreadsheetJob::ownedBy($request)
            ->notExpired()
            ->orderByDesc("created_at")
            ->get()
            ->filter(function($file) {
                $saved_as = storage_path("app/private/{$file->saved_as}");
                $result_path = storage_path("app/private/{$file->result_path}");
                return file_exists($saved_as) || file_exists($result_path);
            })
            ->map(fn(SpreadsheetJob $job) => $job->toFrontendArray())
            ->values()
            ->all();

        return response()->json([
            "files"=> $files,
        ]);
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

        $format_options = [
            "adjust_word_spacing"=> $request->boolean("adjust_word_spacing"),
            "normalize_text"=> $request->boolean("normalize_text"),
            "remove_line_breaks"=> $request->boolean("remove_line_breaks"),
            "text_format"=> $request->input("text_format"),
            "export_format"=> $request->input("export_format", "xlsx"),
        ];

        SpreadsheetJob::create([
            "uuid"=> $uuid,
            "user_id"=> $request->user()?->id,
            "session_id"=> $request->user() ? null : $request->session()->getId(),
            "original_name"=> $original_name,
            "extension"=> mb_strtolower($original_extension),
            "size_bytes"=> $file->getSize(),
            "saved_as"=> $saved_as,
            "status"=> SpreadsheetJob::STATUS_QUEUED,
            "progress"=> 0,
            "format_options"=> $format_options,
            "expires_at"=> now()->addMinutes(30),
        ]);

        ProcessSpreadsheetJob::dispatch($uuid)->onQueue("spreadsheets");

        return response()->json([
            "uuid"=> $uuid,
        ]);
    }

    public function download(Request $request, string $uuid)
    {
        $job = SpreadsheetJob::ownedBy($request)
            ->notExpired()
            ->where("uuid", $uuid)
            ->where("status", SpreadsheetJob::STATUS_DONE)
            ->firstOrFail();

        $file_path = storage_path("app/private/{$job->result_path}");

        abort_unless(file_exists($file_path), Response::HTTP_NOT_FOUND);

        return response()->download($file_path, $job->result_name);
    }

    public function destroy(Request $request, string $uuid)
    {
        $job = SpreadsheetJob::ownedBy($request)
            ->where("uuid", $uuid)
            ->firstOrFail();

        $saved_as = storage_path("app/private/{$job->saved_as}");

        if( file_exists($saved_as) ) {
            unlink($saved_as);
        }

        if( $job->result_path ) {
            $result_path = storage_path("app/private/{$job->result_path}");

            if( file_exists($result_path) ) {
                unlink($result_path);
            }
        }

        return response()->noContent();
    }
}
