<?php

namespace App\Http\Requests\SpreadsheetFormatter;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use App\Helpers\UploadLimit;

class UploadSpreadsheetRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $max_upload_size = UploadLimit::spreadsheetFormatter();

        return [
            "spreadsheet"=> [
                "required",
                "file",
                "max:" . $max_upload_size,
                "mimetypes:text/plain,text/csv,application/csv,application/vnd.ms-excel,application/octet-stream,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
            ],
            "adjust_word_spacing"=> "nullable|boolean",
            "normalize_text"=> "nullable|boolean",
            "text_format"=> "nullable|in:uppercase,lowercase,ucfirst,ucwords",
            "export_format"=> "nullable|in:csv,xlsx",
        ];
    }

    public function messages(): array
    {
        return [
            "spreadsheet.required"=> "O arquivo é obrigatório.",
            "spreadsheet.file"=> "O arquivo enviado não é válido.",
            "spreadsheet.mimetypes"=> "O arquivo deve ser do tipo CSV ou XLSX.",
            "spreadsheet.max"=> "O arquivo não pode ter mais que 4MB.",

            "adjust_word_spacing.boolean"=> "O valor de espaçamento deve ser verdadeiro ou falso.",
            "normalize_text.boolean"=> "O valor de normalização deve ser verdadeiro ou falso.",
            
            "text_format.in"=> "O formato de texto deve ser: uppercase, lowercase ou capitalize.",
            "export_format.in"=> "O formato de exportação deve ser: csv, xlsx.",
        ];
    }
}
