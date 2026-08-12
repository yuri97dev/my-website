<?php

namespace App\Http\Requests\Sgc;

use App\Helpers\TextFormatter;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateCustomerRequest extends SgcFormRequest
{
    protected function prepareForValidation(): void
    {
        $merge = [];

        if( $this->has("name") ) {
            $merge["name"] = TextFormatter::all($this->input("name"));
        }

        if( $this->has("nickname") ) {
            $merge["nickname"] = TextFormatter::all($this->input("nickname"));
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
            "nickname" => ["nullable", "string", "max:255"],
            "identity_document" => ["nullable", "string", "max:255"],
            "tax_id" => ["nullable", "string", "max:255"],
            "birth_date" => ["nullable", "date"],
            "company_id" => [
                "required",
                "integer",
                Rule::exists("sgc_companies", "id"),
            ],
            "notes" => ["nullable", "string"],
            "addresses" => ["nullable", "array"],
            "addresses.*.id" => ["nullable", "integer", "exists:sgc_customer_addresses,id"],
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

    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator) {
            $company_id = $this->integer("company_id");

            if( !$this->companyAccessible($company_id) ) {
                $validator->errors()->add("company_id", "Empresa inválida ou sem permissão.");
            }

            $customer = $this->route("sgcCustomer");

            if( $customer && !$customer->companies()->where("sgc_companies.id", $company_id)->exists() ) {
                $validator->errors()->add("company_id", "Cliente não vinculado a esta empresa.");
            }

            if( $this->filled("notes") && !$this->companyCanManageNotes($company_id) ) {
                $validator->errors()->add("notes", "Apenas o proprietário ou administradores da empresa podem alterar observações.");
            }
        });
    }

    public function messages(): array
    {
        return [
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
            "company_id.required" => "A empresa é obrigatória.",
            "company_id.integer" => "A empresa deve ser um número inteiro.",
            "company_id.exists" => "A empresa selecionada é inválida.",
            "notes.string" => "As observações devem ser um texto válido.",
            "addresses.array" => "Os endereços devem ser uma lista válida.",
            "addresses.*.id.integer" => "O identificador do endereço deve ser um número inteiro.",
            "addresses.*.id.exists" => "O endereço selecionado é inválido.",
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
}
