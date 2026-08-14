<?php

namespace App\Http\Requests\Sgc;

use App\Models\SgcCompany;
use App\Models\SgcCompanyUser;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreEmployeeRequest extends SgcFormRequest
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
            "email" => ["required", "email", "max:255"],
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
            "email.required" => "O e-mail é obrigatório.",
            "email.email" => "O e-mail deve ser um endereço válido.",
            "email.max" => "O e-mail não pode ter mais de 255 caracteres.",
            "role.required" => "O cargo é obrigatório.",
            "role.string" => "O cargo deve ser um texto válido.",
            "role.in" => "O cargo selecionado é inválido.",
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

                return;
            }

            if( !$this->companyCanBeManagedBy($company_id) ) {
                $validator->errors()->add("company_id", "Apenas o proprietário ou administradores da empresa podem adicionar funcionários.");

                return;
            }

            if( !$this->filled("email") ) {
                return;
            }

            $user = User::query()->where("email", $this->input("email"))->first();

            if( !$user ) {
                $validator->errors()->add("email", "Nenhum usuário encontrado com este e-mail.");

                return;
            }

            $company = SgcCompany::query()->find($company_id);

            if( $company && $company->owner_id === $user->id ) {
                $validator->errors()->add("email", "O proprietário da empresa já possui acesso.");

                return;
            }

            $already_exists = SgcCompanyUser::query()
                ->where("company_id", $company_id)
                ->where("user_id", $user->id)
                ->exists();

            if( $already_exists ) {
                $validator->errors()->add("email", "Este usuário já está vinculado à empresa.");
            }
        });
    }
}
