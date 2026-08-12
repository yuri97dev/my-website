<?php

namespace App\Http\Requests\Sgc;

use App\Helpers\TextFormatter;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreCompanyRequest extends SgcFormRequest
{
    protected function prepareForValidation(): void
    {
        $merge = [];

        if( $this->has("legal_name") ) {
            $merge["legal_name"] = TextFormatter::all($this->input("legal_name"));
        }

        if( $this->has("trade_name") ) {
            $merge["trade_name"] = TextFormatter::all($this->input("trade_name"));
        }

        $this->merge($merge);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            "parent_id" => [
                "nullable",
                "integer",
                Rule::exists("sgc_companies", "id"),
            ],
            "legal_name" => ["required", "string", "max:255"],
            "trade_name" => ["nullable", "string", "max:255"],
            "document" => ["nullable", "string", "max:255"],
            "address_street" => ["nullable", "string", "max:255"],
            "address_number" => ["nullable", "string", "max:255"],
            "address_complement" => ["nullable", "string", "max:255"],
            "address_district" => ["nullable", "string", "max:255"],
            "address_city" => ["nullable", "string", "max:255"],
            "address_state" => ["nullable", "string", "max:255"],
            "address_postal_code" => ["nullable", "string", "max:255"],
            "address_country" => ["nullable", "string", "max:255"],
        ];
    }

    public function messages(): array
    {
        return [
            "parent_id.integer" => "A empresa matriz deve ser um número inteiro.",
            "parent_id.exists" => "A empresa matriz selecionada é inválida.",
            "legal_name.required" => "O nome legal é obrigatório.",
            "legal_name.string" => "O nome legal deve ser um texto válido.",
            "legal_name.max" => "O nome legal não pode ter mais de 255 caracteres.",
            "trade_name.string" => "O nome fantasia deve ser um texto válido.",
            "trade_name.max" => "O nome fantasia não pode ter mais de 255 caracteres.",
            "document.string" => "O documento deve ser um texto válido.",
            "document.max" => "O documento não pode ter mais de 255 caracteres.",
            "address_street.string" => "O logradouro deve ser um texto válido.",
            "address_street.max" => "O logradouro não pode ter mais de 255 caracteres.",
            "address_number.string" => "O número deve ser um texto válido.",
            "address_number.max" => "O número não pode ter mais de 255 caracteres.",
            "address_complement.string" => "O complemento deve ser um texto válido.",
            "address_complement.max" => "O complemento não pode ter mais de 255 caracteres.",
            "address_district.string" => "O bairro deve ser um texto válido.",
            "address_district.max" => "O bairro não pode ter mais de 255 caracteres.",
            "address_city.string" => "A cidade deve ser um texto válido.",
            "address_city.max" => "A cidade não pode ter mais de 255 caracteres.",
            "address_state.string" => "O estado deve ser um texto válido.",
            "address_state.max" => "O estado não pode ter mais de 255 caracteres.",
            "address_postal_code.string" => "O CEP deve ser um texto válido.",
            "address_postal_code.max" => "O CEP não pode ter mais de 255 caracteres.",
            "address_country.string" => "O país deve ser um texto válido.",
            "address_country.max" => "O país não pode ter mais de 255 caracteres.",
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator) {
            if( $this->filled("parent_id") && !$this->companyAccessible($this->integer("parent_id")) ) {
                $validator->errors()->add("parent_id", "Empresa matriz inválida ou sem permissão.");
            }
        });
    }
}
