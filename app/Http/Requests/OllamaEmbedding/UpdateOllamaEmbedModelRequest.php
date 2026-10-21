<?php

namespace App\Http\Requests\OllamaEmbedding;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateOllamaEmbedModelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->isSystemAdmin();
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            "embed_model" => ["required", "string", "max:100"],
        ];
    }

    public function messages(): array
    {
        return [
            "embed_model.required" => "O modelo de embedding é obrigatório.",
            "embed_model.max" => "O modelo de embedding não pode ter mais de 100 caracteres.",
        ];
    }
}
