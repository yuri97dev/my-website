<?php

namespace App\Http\Requests\AiProvider;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use App\Helpers\TextFormatter;

class StoreAiProviderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->isSystemAdmin();
    }

    protected function prepareForValidation(): void
    {
        $merge = [];

        if( $this->has("name") ) {
            $merge["name"] = TextFormatter::removeExtraSpaces($this->input("name"));
        }

        if( $this->filled("provider") ) {
            $merge["provider"] = Str::slug($this->input("provider"));
        }

        if( $this->has("url") ) {
            $merge["url"] = trim($this->input("url"));
        }

        $this->merge($merge);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            "name" => [
                "required",
                "string",
                "max:100",
                Rule::unique("ai_providers", "name"),
            ],
            "provider" => ["required", "string", "max:50"],
            "url" => ["required", "string", "url", "max:500"],
            "api_key" => ["nullable", "string"],
            "is_enabled" => ["sometimes", "boolean"],
        ];
    }

    public function messages(): array
    {
        return [
            "name.required" => "O nome é obrigatório.",
            "name.max" => "O nome não pode ter mais de 100 caracteres.",
            "name.unique" => "Este nome já está em uso.",
            "provider.required" => "O provedor é obrigatório.",
            "provider.max" => "O provedor não pode ter mais de 50 caracteres.",
            "url.required" => "A URL é obrigatória.",
            "url.url" => "A URL deve ser válida.",
            "url.max" => "A URL não pode ter mais de 500 caracteres.",
            "api_key.string" => "A chave de API deve ser um texto válido.",
            "is_enabled.boolean" => "O campo habilitado deve ser verdadeiro ou falso.",
        ];
    }
}
