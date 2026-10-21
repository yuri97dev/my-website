<?php

namespace App\Http\Requests\OllamaEmbedding;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class PullOllamaModelRequest extends FormRequest
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
            "model" => ["required", "string", "max:100"],
        ];
    }

    public function messages(): array
    {
        return [
            "model.required" => "O nome do modelo é obrigatório.",
            "model.max" => "O nome do modelo não pode ter mais de 100 caracteres.",
        ];
    }
}
