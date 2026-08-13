<?php

namespace App\Http\Requests\Sgc;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Validator;

class StoreCustomerAddressRequest extends SgcFormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
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
            $customer = $this->route("sgc_customer");

            if( !$customer || !$customer->isAccessibleBy($this->user()) ) {
                $validator->errors()->add("customer", "Cliente inválido ou sem permissão.");
            }
        });
    }
}
