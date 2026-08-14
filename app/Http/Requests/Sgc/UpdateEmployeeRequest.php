<?php

namespace App\Http\Requests\Sgc;

use App\Models\SgcCompany;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateEmployeeRequest extends SgcFormRequest
{
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
            "role" => [
                "required",
                "string",
                Rule::in([
                    SgcCompany::ROLE_ADMIN,
                    SgcCompany::ROLE_SELLER,
                ]),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            "company_id.required" => "A empresa é obrigatória.",
            "company_id.integer" => "A empresa deve ser um número inteiro.",
            "company_id.exists" => "A empresa selecionada é inválida.",
            "role.required" => "O cargo é obrigatório.",
            "role.string" => "O cargo deve ser um texto válido.",
            "role.in" => "O cargo selecionado é inválido.",
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator) {
            $company_id = $this->integer("company_id");
            $employee = $this->route("sgc_company_user");

            if( !$this->companyAccessible($company_id) ) {
                $validator->errors()->add("company_id", "Empresa inválida ou sem permissão.");

                return;
            }

            if( !$this->companyCanBeManagedBy($company_id) ) {
                $validator->errors()->add("company_id", "Apenas o proprietário ou administradores da empresa podem alterar funcionários.");

                return;
            }

            if( $employee && (int) $employee->company_id !== $company_id ) {
                $validator->errors()->add("company_id", "Funcionário não vinculado a esta empresa.");

                return;
            }

            if( $employee?->isOwner() ) {
                $validator->errors()->add("role", "O cargo do proprietário da empresa não pode ser alterado.");
            }
        });
    }
}
