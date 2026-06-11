<?php

namespace App\Http\Requests\Blog;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use App\Helpers\TextFormatter;

class UpdatePostCategoryRequest extends FormRequest
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

        if( $this->filled("slug") ) {
            $merge["slug"] = Str::slug($this->input("slug"));
        }

        $this->merge($merge);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            "name" => ["sometimes", "required", "string", "max:255"],
            "slug" => [
                "sometimes",
                "required",
                "string",
                "max:255",
                Rule::unique("post_categories", "slug")
                    ->ignore($this->route("postCategory")),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            "name.required" => "O nome é obrigatório.",
            "name.string" => "O nome deve ser um texto válido.",
            "name.max" => "O nome não pode ter mais de 255 caracteres.",
            "slug.required" => "O slug é obrigatório.",
            "slug.string" => "O slug deve ser um texto válido.",
            "slug.max" => "O slug não pode ter mais de 255 caracteres.",
            "slug.unique" => "Este slug já está em uso. Informe outro slug.",
        ];
    }
}
