<?php

namespace App\Http\Requests\Sgc;

use App\Helpers\TextFormatter;
use App\Models\SgcCustomer;
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

        if( $this->has("identity_document") ) {
            $merge["identity_document"] = SgcCustomer::normalizeIdentityDocument(
                $this->input("identity_document")
            );
        }

        if( $this->has("tax_id") ) {
            $merge["tax_id"] = SgcCustomer::normalizeTaxId($this->input("tax_id"));
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
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator) {
            $company_id = $this->integer("company_id");

            if( !$this->companyAccessible($company_id) ) {
                $validator->errors()->add("company_id", "Empresa inválida ou sem permissão.");
            }

            $customer = $this->route("sgc_customer");

            if( $customer && !$customer->companies()->where("sgc_companies.id", $company_id)->exists() ) {
                $validator->errors()->add("company_id", "Cliente não vinculado a esta empresa.");
            }

            if( $this->filled("notes") && !$this->companyCanBeManagedBy($company_id) ) {
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
        ];
    }
}
