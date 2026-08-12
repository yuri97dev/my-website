<?php

namespace App\Http\Requests\Sgc;

use App\Helpers\TextFormatter;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreCustomerRequest extends SgcFormRequest
{
    protected function prepareForValidation(): void
    {
        if( $this->has("name") ) {
            $this->merge([
                "name" => TextFormatter::all($this->input("name")),
            ]);
        }

        if( $this->has("nickname") ) {
            $this->merge([
                "nickname" => TextFormatter::all($this->input("nickname")),
            ]);
        }
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            "company_id" => [
                "required",
                "integer",
                Rule::exists("sgc_companies", "id"),
            ],
            "name" => ["required", "string", "max:255"],
            "nickname" => ["nullable", "string", "max:255"],
            "identity_document" => ["nullable", "string", "max:255"],
            "tax_id" => ["nullable", "string", "max:255"],
            "birth_date" => ["nullable", "date"],
            "notes" => ["nullable", "string"],
            "addresses" => ["nullable", "array"],
            "addresses.*.address_street" => ["nullable", "string", "max:255"],
            "addresses.*.address_number" => ["nullable", "string", "max:255"],
            "addresses.*.address_complement" => ["nullable", "string", "max:255"],
            "addresses.*.address_district" => ["nullable", "string", "max:255"],
            "addresses.*.address_city" => ["nullable", "string", "max:255"],
            "addresses.*.address_state" => ["nullable", "string", "max:255"],
            "addresses.*.address_postal_code" => ["nullable", "string", "max:255"],
            "addresses.*.address_country" => ["nullable", "string", "max:255"],
        ];
    }

    public function messages(): array
    {
        return [
            "company_id.required" => "A empresa é obrigatória.",
            "company_id.integer" => "A empresa deve ser um número inteiro.",
            "company_id.exists" => "A empresa selecionada é inválida.",
            "name.required" => "O nome é obrigatório.",
            "name.string" => "O nome deve ser um texto válido.",
            "name.max" => "O nome não pode ter mais de 255 caracteres.",
            "nickname.string" => "O apelido deve ser um texto válido.",
            "nickname.max" => "O apelido não pode ter mais de 255 caracteres.",
            "identity_document.string" => "O documento de identidade deve ser um texto válido.",
            "identity_document.max" => "O documento de identidade não pode ter mais de 255 caracteres.",
            "tax_id.string" => "O CPF/CNPJ deve ser um texto válido.",
            "tax_id.max" => "O CPF/CNPJ não pode ter mais de 255 caracteres.",
            "birth_date.date" => "A data de nascimento deve ser uma data válida.",
            "notes.string" => "As observações devem ser um texto válido.",
            "addresses.array" => "Os endereços devem ser uma lista válida.",
            "addresses.*.address_street.string" => "O logradouro deve ser um texto válido.",
            "addresses.*.address_street.max" => "O logradouro não pode ter mais de 255 caracteres.",
            "addresses.*.address_number.string" => "O número deve ser um texto válido.",
            "addresses.*.address_number.max" => "O número não pode ter mais de 255 caracteres.",
            "addresses.*.address_complement.string" => "O complemento deve ser um texto válido.",
            "addresses.*.address_complement.max" => "O complemento não pode ter mais de 255 caracteres.",
            "addresses.*.address_district.string" => "O bairro deve ser um texto válido.",
            "addresses.*.address_district.max" => "O bairro não pode ter mais de 255 caracteres.",
            "addresses.*.address_city.string" => "A cidade deve ser um texto válido.",
            "addresses.*.address_city.max" => "A cidade não pode ter mais de 255 caracteres.",
            "addresses.*.address_state.string" => "O estado deve ser um texto válido.",
            "addresses.*.address_state.max" => "O estado não pode ter mais de 255 caracteres.",
            "addresses.*.address_postal_code.string" => "O CEP deve ser um texto válido.",
            "addresses.*.address_postal_code.max" => "O CEP não pode ter mais de 255 caracteres.",
            "addresses.*.address_country.string" => "O país deve ser um texto válido.",
            "addresses.*.address_country.max" => "O país não pode ter mais de 255 caracteres.",
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator) {
            if( !$this->filled("company_id") ) {
                return;
            }

            $company_id = $this->integer("company_id");

            if( !$this->companyAccessible($company_id) ) {
                $validator->errors()->add("company_id", "Empresa inválida ou sem permissão.");
            }

            if( $this->filled("notes") && !$this->companyCanManageNotes($company_id) ) {
                $validator->errors()->add("notes", "Apenas o proprietário ou administradores da empresa podem adicionar observações.");
            }
        });
    }
}
