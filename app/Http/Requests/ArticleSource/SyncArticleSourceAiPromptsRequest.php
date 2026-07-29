<?php

namespace App\Http\Requests\ArticleSource;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class SyncArticleSourceAiPromptsRequest extends FormRequest
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
            "ai_prompt_ids" => ["present", "array"],
            "ai_prompt_ids.*" => ["integer", "distinct", "exists:ai_prompts,id"],
        ];
    }

    public function messages(): array
    {
        return [
            "ai_prompt_ids.present" => "A lista de prompts é obrigatória.",
            "ai_prompt_ids.array" => "A lista de prompts deve ser um array.",
            "ai_prompt_ids.*.integer" => "Cada prompt deve ser um identificador válido.",
            "ai_prompt_ids.*.distinct" => "Não é permitido repetir o mesmo prompt.",
            "ai_prompt_ids.*.exists" => "Um ou mais prompts selecionados não existem.",
        ];
    }
}
