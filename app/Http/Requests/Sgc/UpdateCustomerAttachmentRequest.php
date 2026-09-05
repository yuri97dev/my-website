<?php

namespace App\Http\Requests\Sgc;

use App\Models\SgcCustomer;
use App\Models\SgcCustomerAttachment;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateCustomerAttachmentRequest extends SgcFormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            "type" => [
                "required",
                "string",
                Rule::in(SgcCustomerAttachment::types()),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            "type.required" => "O tipo do anexo é obrigatório.",
            "type.in" => "O tipo do anexo é inválido.",
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator) {
            $customer = $this->route("sgc_customer");
            $attachment = $this->route("sgc_customer_attachment");

            if( !$customer instanceof SgcCustomer ) {
                $validator->errors()->add("customer", "Cliente inválido ou sem permissão.");

                return;
            }

            if( $customer->isFinalConsumer() ) {
                $validator->errors()->add("customer", "O consumidor final não pode receber anexos.");

                return;
            }

            if( !$attachment instanceof SgcCustomerAttachment ) {
                $validator->errors()->add("attachment", "Anexo inválido.");

                return;
            }

            if( (int) $attachment->companyCustomer?->customer_id !== (int) $customer->id ) {
                $validator->errors()->add("attachment", "Anexo inválido para este cliente.");

                return;
            }

            if( !$attachment->isAccessibleBy($this->user()) ) {
                $validator->errors()->add("attachment", "Anexo inválido ou sem permissão.");
            }
        });
    }
}
