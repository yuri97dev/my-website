<?php

namespace App\Http\Requests\AiProvider;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use App\Helpers\TextFormatter;

class UpdateAiProviderRequest extends FormRequest
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

        $this->merge($merge);
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
                Rule::unique("ai_providers", "name")
                    ->ignore($this->route("aiProvider")),
            ],
            "provider" => ["sometimes", "required", "string", "max:50"],
            "api_key" => ["sometimes", "nullable", "string"],
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
            "api_key.string" => "A chave de API deve ser um texto válido.",
            "is_enabled.boolean" => "O campo habilitado deve ser verdadeiro ou falso.",
        ];
    }
}
