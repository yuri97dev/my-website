<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Ark4ne\XlReader\Factory;
use App\Helpers\TextFormatter;
use App\Models\SpreadsheetJob;
use XLSXWriter;
use Exception;

class ProcessSpreadsheetJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private const PROGRESS_UPDATE_THRESHOLD = 4;

    public function __construct(
        protected string $uuid
    ) {}

    public function handle(): void
    {
        $record = SpreadsheetJob::where("uuid", $this->uuid)->firstOrFail();

        if( $record->status === SpreadsheetJob::STATUS_CANCELLED ) {
            return;
        }

        $writer = null;
        $spreadsheet_file = storage_path("app/private/{$record->saved_as}");

        $record->update([
            "status"=> SpreadsheetJob::STATUS_PROCESSING,
            "progress"=> 0,
        ]);

        try {
            $num_rows = 0;
            $progress = 0;
            $last_progress = 0;

            if( str_ends_with(mb_strtolower($spreadsheet_file), ".csv") ) {
                $this->ensureUtf8Bom($spreadsheet_file);
            }

            $reader = Factory::createReader($spreadsheet_file);
            $reader->load();

            foreach($reader->read() as $row) {
                $num_rows++;
            }

            $headers = $reader->read(1, 1)->current();
            $excel_columns = array_keys($headers);

            $formatters = $this->getFormatters($record);
            $writer = $this->getWriter($record);

            foreach($reader->read() as $index => $row) {
                $processed_row = [];

                foreach($excel_columns as $column) {
                    $value = $row[$column] ?? null;

                    foreach($formatters as $formatter) {
                        $value = $formatter($value);
                    }

                    $processed_row[$column] = $value;
                }

                ($writer->add)($processed_row);

                $progress = ceil(($index / $num_rows) * 100);
                $progress = max(0, $progress - 1);

                $update_progress = ($progress - $last_progress) >= self::PROGRESS_UPDATE_THRESHOLD;

                if( $update_progress ) {
                    $record->refresh();

                    if( $record->status === SpreadsheetJob::STATUS_CANCELLED ) {
                        break;
                    }

                    $last_progress = $progress;
                    $record->update(["progress"=> $progress]);
                }
            }

            $record->refresh();

            if( $record->status === SpreadsheetJob::STATUS_CANCELLED ) {
                ($writer->delete)();
                return;
            }

            if( $progress == 99 ) {
                [ $result_path, $result_name ] = ($writer->save)();

                $record->update([
                    "status"=> SpreadsheetJob::STATUS_DONE,
                    "progress"=> 100,
                    "result_path"=> $this->toStorageRelativePath($result_path),
                    "result_name"=> $result_name,
                ]);
            } else {
                ($writer->delete)();
            }
        } catch (\Throwable $e) {
            Log::error($e->getMessage());

            $record->update([
                "status"=> SpreadsheetJob::STATUS_FAILED,
                "error_message"=> $e->getMessage(),
            ]);

            $writer && ($writer->delete)();
        } finally {
            file_exists($spreadsheet_file) && unlink($spreadsheet_file);
        }
    }

    private function toStorageRelativePath(string $absolute_path): string
    {
        $private_path = storage_path("app/private/");

        return str_starts_with($absolute_path, $private_path)
            ? substr($absolute_path, strlen($private_path))
            : $absolute_path;
    }

    private function getFormatters(SpreadsheetJob $record)
    {
        $format_options = (object) $record->format_options;

        $text_formats = [
            "uppercase"=> fn($text) => mb_strtoupper($text),
            "lowercase"=> fn($text) => mb_strtolower($text),
            "ucfirst"=> fn($text) => ucfirst(mb_strtolower($text)),
            "ucwords"=> fn($text) => ucwords(mb_strtolower($text)),
            "original"=> fn($text) => $text,
        ];

        $all_formatters = [
            "remove_line_breaks"=> function($text) {
                $normalized_text = preg_replace("/(\r\n|\n\r|\r|\n)+/", "\n", $text);
                $normalized_text = trim($normalized_text, "\n");

                $text_with_punctuation_space = preg_replace("/([.!?,;:])\n/", "$1 ", $normalized_text);
                $final_text = str_replace("\n", ". ", $text_with_punctuation_space);

                return trim($final_text);
            },
            "adjust_word_spacing"=> fn($text) => TextFormatter::removeExtraSpaces($text),
            "normalize_text"=> fn($text) => TextFormatter::removeDiacritics($text),
            "text_format"=> function ($text, $option_value) use ($text_formats) {
                $option_value = $option_value ?? "original";
                $formatter = $text_formats[$option_value] ?? $text_formats["original"];
                return $formatter($text);
            },
        ];

        $selected_formatters = [
            fn($text) => TextFormatter::removeHTML($text),
        ];

        foreach($format_options as $option => $value) {
            $formatter = $all_formatters[$option] ?? null;

            if( empty($value) || !$formatter ) continue;

            $selected_formatters[] = fn($text) => $formatter($text, $value);
        }

        return $selected_formatters;
    }

    private function getWriter(SpreadsheetJob $record)
    {
        $format_options = $record->format_options;
        $original_name = $record->original_name;
        $export_format = $format_options["export_format"];

        $saved_file_info = (object) pathinfo($record->saved_as);
        $original_name_info = (object) pathinfo($original_name);

        $files_path = storage_path("app/private");

        $save_as = "{$files_path}/{$saved_file_info->dirname}/{$saved_file_info->filename}(2).{$export_format}";
        $export_as = "{$original_name_info->filename}.{$export_format}";

        if( $export_format == "xlsx" ) {
            $writer = new XLSXWriter();

            return (object) [
                "add"=> fn($row) => $writer->writeSheetRow($original_name, $row),
                "save"=> function() use ($save_as, $export_as, $writer) {
                    $writer->writeToFile($save_as);
                    return [ $save_as, $export_as ];
                },
                "delete"=> fn() => file_exists($save_as) && unlink($save_as),
            ];
        }

        if( $export_format == "csv" ) {
            $fp = fopen($save_as, "w");

            if( !$fp ) throw new Exception("Failed to open stream");

            fwrite($fp, "\xEF\xBB\xBF");

            return (object) [
                "add"=> fn($row) => fputcsv($fp, $row, ";", '"', ""),
                "save"=> function() use ($save_as, $export_as, $fp) {
                    is_resource($fp) && fclose($fp);
                    return [ $save_as, $export_as ];
                },
                "delete"=> function() use ($save_as, $fp) {
                    file_exists($save_as) && unlink($save_as);
                    is_resource($fp) && fclose($fp);
                },
            ];
        }

        throw new Exception("Invalid format");
    }

    private function ensureUtf8Bom(string $original_file_path)
    {
        $original_file_stream = fopen($original_file_path, "r");

        if( !$original_file_stream ) {
            throw new Exception("Failed to open file {$original_file_path}");
        }

        $sample = fread($original_file_stream, 2048);
        rewind($original_file_stream);

        $bom_map = [
            "\xEF\xBB\xBF"=> 3, // UTF-8
            "\xFF\xFE"=> 2, // UTF-16LE
            "\xFE\xFF"=> 2, // UTF-16BE
            "\xFF\xFE\x00\x00"=> 4, // UTF-32LE
            "\x00\x00\xFE\xFF"=> 4, // UTF-32BE
        ];

        $bom_length = 0;

        foreach($bom_map as $bom => $length)
        {
            if( strncmp($sample, $bom, $length) !== 0 ) continue;
            $bom_length = $length;
            break;
        }

        if( $bom_length > 0 ) {
            $sample = substr($sample, $bom_length);
            fseek($original_file_stream, $bom_length);
        }

        $encoding = mb_detect_encoding($sample, [
            "ISO-8859-15", "ISO-8859-1", "Windows-1252",
            "UTF-8", "UTF-16LE", "UTF-16BE", "UTF-32LE", "UTF-32BE",
        ], true);

        if( $encoding && $encoding != "UTF-8" ) {
            stream_filter_append($original_file_stream, "convert.iconv.{$encoding}/UTF-8");
        }

        $tmp_file_path = "{$original_file_path}.bomtmp";

        $tmp_file_stream = fopen($tmp_file_path, "w");

        if( !$tmp_file_stream ) {
            fclose($original_file_stream);
            throw new Exception("Failed to create temp file {$tmp_file_path}");
        }

        stream_copy_to_stream($original_file_stream, $tmp_file_stream);

        fclose($original_file_stream);
        fclose($tmp_file_stream);

        if( !rename($tmp_file_path, $original_file_path) ) {
            unlink($tmp_file_path);
            throw new Exception("Failed to rename temp file {$tmp_file_path} to {$original_file_path}");
        }
    }
}
