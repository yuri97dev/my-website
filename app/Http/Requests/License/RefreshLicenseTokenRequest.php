<?php

namespace App\Http\Requests\License;

use Illuminate\Foundation\Http\FormRequest;

class RefreshLicenseTokenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            "fingerprint"=> ["required", "string", "max:255"],
            "jwt_payload"=> ["required", "array"],
        ];
    }

    public function messages(): array
    {
        return [
            "fingerprint.required"=> "O identificador do dispositivo é obrigatório.",
            "fingerprint.max"=> "O identificador do dispositivo não pode exceder 255 caracteres.",
            "jwt_payload.required"=> "Payload JWT inválido.",
        ];
    }
}
