<?php

namespace App\Http\Requests\Sgc;

use App\Helpers\TextFormatter;
use Illuminate\Contracts\Validation\ValidationRule;

class StoreProductCategoryRequest extends SgcFormRequest
{
    protected function prepareForValidation(): void
    {
        if( $this->has("name") ) {
            $this->merge([
                "name" => TextFormatter::all($this->input("name")),
            ]);
        }
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            "name" => ["required", "string", "max:255"],
            "description" => ["nullable", "string"],
        ];
    }

    public function messages(): array
    {
        return [
            "name.required" => "O nome é obrigatório.",
            "name.string" => "O nome deve ser um texto válido.",
            "name.max" => "O nome não pode ter mais de 255 caracteres.",
            "description.string" => "A descrição deve ser um texto válido.",
        ];
    }
}
