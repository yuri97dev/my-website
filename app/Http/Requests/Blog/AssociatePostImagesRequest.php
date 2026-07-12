<?php

namespace App\Http\Requests\Blog;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class AssociatePostImagesRequest extends FormRequest
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
            "post_id" => ["required", "integer", "exists:posts,id"],
            "paths" => ["required", "array", "min:1"],
            "paths.*" => ["required", "string", "max:500"],
        ];
    }

    public function messages(): array
    {
        return [
            "post_id.required" => "O post é obrigatório.",
            "post_id.integer" => "O post informado é inválido.",
            "post_id.exists" => "O post informado não existe.",
            "paths.required" => "Informe ao menos um caminho de imagem.",
            "paths.array" => "Os caminhos devem ser enviados em uma lista.",
            "paths.min" => "Informe ao menos um caminho de imagem.",
            "paths.*.required" => "O caminho da imagem é obrigatório.",
            "paths.*.string" => "O caminho da imagem deve ser um texto válido.",
            "paths.*.max" => "O caminho da imagem não pode ter mais de 500 caracteres.",
        ];
    }
}
