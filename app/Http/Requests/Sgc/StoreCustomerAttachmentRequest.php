<?php

namespace App\Http\Requests\Sgc;

use App\Models\SgcCustomer;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreCustomerAttachmentRequest extends SgcFormRequest
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
            "file" => [
                "required",
                "file",
                "max:10240",
                "mimes:pdf,jpg,jpeg,png,webp,doc,docx",
            ],
        ];
    }

    public function messages(): array
    {
        return [
            "company_id.required" => "A empresa é obrigatória.",
            "company_id.integer" => "A empresa deve ser um número inteiro.",
            "company_id.exists" => "A empresa selecionada é inválida.",
            "file.required" => "O arquivo é obrigatório.",
            "file.file" => "O arquivo enviado é inválido.",
            "file.max" => "O arquivo não pode ter mais de 10 MB.",
            "file.mimes" => "O arquivo deve ser PDF, JPEG, PNG, WebP, DOC ou DOCX.",
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator) {
            $customer = $this->route("sgc_customer");

            if( !$customer instanceof SgcCustomer ) {
                $validator->errors()->add("customer", "Cliente inválido ou sem permissão.");

                return;
            }

            if( $customer->isFinalConsumer() ) {
                $validator->errors()->add("customer", "O consumidor final não pode receber anexos.");

                return;
            }

            if( !$this->filled("company_id") ) {
                return;
            }

            $company_id = $this->integer("company_id");

            if( !$this->companyAccessible($company_id) ) {
                $validator->errors()->add("company_id", "Empresa inválida ou sem permissão.");

                return;
            }

            if( !$customer->companyCustomer($company_id) ) {
                $validator->errors()->add("company_id", "Cliente não vinculado a esta empresa.");
            }
        });
    }
}
