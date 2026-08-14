<?php

namespace App\Http\Requests\Sgc;

use App\Helpers\TextFormatter;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

class StoreUnitOfMeasureRequest extends SgcFormRequest
{
    protected function prepareForValidation(): void
    {
        if( $this->has("name") ) {
            $this->merge([
                "name" => TextFormatter::all($this->input("name")),
            ]);
        }

        if( $this->has("abbreviation") ) {
            $this->merge([
                "abbreviation" => strtoupper(trim($this->input("abbreviation"))),
            ]);
        }
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            "abbreviation" => [
                "required",
                "string",
                "max:8",
                Rule::unique("sgc_units_of_measure", "abbreviation"),
            ],
            "name" => [
                "required",
                "string",
                "max:255",
                Rule::unique("sgc_units_of_measure", "name"),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            "abbreviation.required" => "A abreviação é obrigatória.",
            "abbreviation.string" => "A abreviação deve ser um texto válido.",
            "abbreviation.max" => "A abreviação não pode ter mais de 8 caracteres.",
            "abbreviation.unique" => "Já existe uma unidade com esta abreviação.",
            "name.required" => "O nome é obrigatório.",
            "name.string" => "O nome deve ser um texto válido.",
            "name.max" => "O nome não pode ter mais de 255 caracteres.",
            "name.unique" => "Já existe uma unidade com este nome.",
        ];
    }
}
