<?php

namespace App\Http\Requests\Blog;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use App\Models\Post;
use App\Helpers\TextFormatter;

class UpdatePostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->isSystemAdmin();
    }

    protected function prepareForValidation(): void
    {
        $merge = [];

        if( $this->has("title") ) {
            $merge["title"] = TextFormatter::removeExtraSpaces($this->input("title"));
        }

        if( $this->filled("slug") ) {
            $merge["slug"] = Str::slug($this->input("slug"));
        }

        if( $this->has("excerpt") ) {
            $merge["excerpt"] = TextFormatter::removeExtraSpaces($this->input("excerpt"));
        }

        if( $this->has("meta_description") ) {
            $merge["meta_description"] = TextFormatter::removeExtraSpaces(
                $this->input("meta_description")
            );
        }

        $this->merge($merge);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            "title" => ["sometimes", "required", "string", "max:200"],
            "slug" => [
                "sometimes",
                "required",
                "string",
                "max:255",
                Rule::unique("posts", "slug")
                    ->ignore($this->route("post")),
            ],
            "category_id" => [
                "sometimes",
                "nullable",
                "integer",
                Rule::exists("post_categories", "id"),
            ],
            "status" => [
                "sometimes",
                "string",
                Rule::in([
                    Post::STATUS_DRAFT,
                    Post::STATUS_PUBLISHED,
                    Post::STATUS_ARCHIVED,
                ]),
            ],
            "excerpt" => ["sometimes", "nullable", "string", "max:500"],
            "content" => ["sometimes", "required", "string"],
            "meta_description" => ["sometimes", "nullable", "string", "max:160"],
            "thumbnail_path" => ["sometimes", "nullable", "string", "max:500"],
            "tags" => ["sometimes", "array"],
            "tags.*" => [
                "integer",
                Rule::exists("post_tags", "id"),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            "title.required" => "O título é obrigatório.",
            "title.string" => "O título deve ser um texto válido.",
            "title.max" => "O título não pode ter mais de 200 caracteres.",
            "slug.required" => "O slug é obrigatório.",
            "slug.string" => "O slug deve ser um texto válido.",
            "slug.max" => "O slug não pode ter mais de 255 caracteres.",
            "slug.unique" => "Este slug já está em uso. Informe outro slug.",
            "category_id.integer" => "A categoria deve ser um identificador válido.",
            "category_id.exists" => "A categoria informada não existe.",
            "status.string" => "O status deve ser um texto válido.",
            "status.in" => "O status informado é inválido.",
            "excerpt.string" => "O resumo deve ser um texto válido.",
            "excerpt.max" => "O resumo não pode ter mais de 500 caracteres.",
            "content.required" => "O conteúdo é obrigatório.",
            "content.string" => "O conteúdo deve ser um texto válido.",
            "meta_description.string" => "A meta descrição deve ser um texto válido.",
            "meta_description.max" => "A meta descrição não pode ter mais de 160 caracteres.",
            "thumbnail_path.string" => "A miniatura deve ser um caminho válido.",
            "thumbnail_path.max" => "O caminho da miniatura não pode ter mais de 500 caracteres.",
            "tags.array" => "As tags devem ser informadas como uma lista.",
            "tags.*.integer" => "Cada tag deve ser um identificador válido.",
            "tags.*.exists" => "Uma ou mais tags informadas não existem.",
        ];
    }
}
