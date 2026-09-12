<?php

namespace App\Http\Requests\License;

use App\Models\License;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateLicenseStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user();
    }

    public function rules(): array
    {
        return [
            "status"=> [
                "required",
                Rule::in([
                    License::STATUS_PENDING,
                    License::STATUS_ACTIVE,
                    License::STATUS_SUSPENDED,
                    License::STATUS_REVOKED,
                ]),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            "status.required"=> "O campo status é obrigatório.",
            "status.in"=> "Status inválido ou inexistente.",
        ];
    }
}
