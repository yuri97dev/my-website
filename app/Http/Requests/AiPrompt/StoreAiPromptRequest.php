<?php

namespace App\Http\Requests\AiPrompt;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StoreAiPromptRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->isSystemAdmin();
    }

    protected function prepareForValidation(): void
    {
        if( $this->filled("key") ) {
            $this->merge([
                "key" => Str::slug($this->input("key")),
            ]);
        }
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $aiProvider = $this->route("aiProvider");

        return [
            "key" => [
                "required",
                "string",
                "max:100",
                Rule::unique("ai_prompts", "key")
                    ->where("ai_provider_id", $aiProvider->id),
            ],
            "content" => ["required", "string"],
        ];
    }

    public function messages(): array
    {
        return [
            "key.required" => "A chave é obrigatória.",
            "key.max" => "A chave não pode ter mais de 100 caracteres.",
            "key.unique" => "Esta chave já está em uso para este provedor.",
            "content.required" => "O conteúdo é obrigatório.",
            "content.string" => "O conteúdo deve ser um texto válido.",
        ];
    }
}
