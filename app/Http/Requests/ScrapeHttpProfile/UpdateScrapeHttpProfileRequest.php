<?php

namespace App\Http\Requests\ScrapeHttpProfile;

use App\Helpers\TextFormatter;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateScrapeHttpProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->isSystemAdmin();
    }

    protected function prepareForValidation(): void
    {
        if( $this->has("name") ) {
            $this->merge([
                "name" => TextFormatter::removeExtraSpaces($this->input("name")),
            ]);
        }
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            "name" => [
                "sometimes",
                "required",
                "string",
                "max:100",
                Rule::unique("scrape_http_profiles", "name")
                    ->ignore($this->route("scrape_http_profile")),
            ],
            "headers" => ["sometimes", "required", "array", "min:1"],
            "headers.*" => ["required", "string"],
            "is_enabled" => ["sometimes", "boolean"],
        ];
    }

    public function messages(): array
    {
        return [
            "name.required" => "O nome é obrigatório.",
            "name.max" => "O nome não pode ter mais de 100 caracteres.",
            "name.unique" => "Este nome já está em uso.",
            "headers.required" => "Os headers são obrigatórios.",
            "headers.array" => "Os headers devem ser um objeto.",
            "headers.min" => "Informe ao menos um header.",
            "headers.*.required" => "O valor do header é obrigatório.",
            "headers.*.string" => "O valor do header deve ser um texto válido.",
            "is_enabled.boolean" => "O campo habilitado deve ser verdadeiro ou falso.",
        ];
    }
}
