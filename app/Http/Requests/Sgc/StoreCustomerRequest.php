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

            if( $this->filled("notes") && !$this->companyCanBeManagedBy($company_id) ) {
                $validator->errors()->add("notes", "Apenas o proprietário ou administradores da empresa podem adicionar observações.");
            }
        });
    }
}
