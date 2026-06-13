<?php

namespace App\Http\Requests\Blog;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StorePostImageRequest extends FormRequest
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
            "image" => [
                "required",
                "file",
                "image",
                "mimes:jpeg,jpg,png,gif,webp",
                "max:5120",
            ],
            "post_id" => ["nullable", "integer", "exists:posts,id"],
        ];
    }

    public function messages(): array
    {
        return [
            "image.required" => "A imagem é obrigatória.",
            "image.file" => "O arquivo enviado é inválido.",
            "image.image" => "O arquivo deve ser uma imagem.",
            "image.mimes" => "A imagem deve ser JPEG, PNG, GIF ou WebP.",
            "image.max" => "A imagem não pode ter mais de 5 MB.",
            "post_id.integer" => "O post informado é inválido.",
            "post_id.exists" => "O post informado não existe.",
        ];
    }
}
