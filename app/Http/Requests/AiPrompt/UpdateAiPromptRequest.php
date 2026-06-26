<?php

namespace App\Http\Requests\AiPrompt;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class UpdateAiPromptRequest extends FormRequest
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

        if( $this->has("model") ) {
            $this->merge([
                "model" => trim($this->input("model")),
            ]);
        }
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $aiPrompt = $this->route("aiPrompt");

        return [
            "key" => [
                "sometimes",
                "required",
                "string",
                "max:100",
                Rule::unique("ai_prompts", "key")
                    ->where("ai_provider_id", $aiPrompt->ai_provider_id)
                    ->ignore($aiPrompt),
            ],
            "content" => ["sometimes", "required", "string"],
            "model" => ["sometimes", "required", "string", "max:100"],
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
            "model.required" => "O modelo é obrigatório.",
            "model.max" => "O modelo não pode ter mais de 100 caracteres.",
        ];
    }
}
