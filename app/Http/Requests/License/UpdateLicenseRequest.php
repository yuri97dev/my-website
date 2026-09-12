<?php

namespace App\Http\Requests\License;

use Illuminate\Foundation\Http\FormRequest;

class UpdateLicenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user();
    }

    public function rules(): array
    {
        return [
            "label"=> ["required", "string", "max:255"],
            "observations"=> ["nullable", "string", "max:5000"],
            "expires_at"=> ["required", "date"],
        ];
    }

    public function messages(): array
    {
        return [
            "label.required"=> "O nome da licença é obrigatório.",
            "label.string"=> "O nome da licença deve ser um texto.",
            "label.max"=> "O nome da licença não pode exceder 255 caracteres.",
            "observations.string"=> "As observações devem ser um texto.",
            "observations.max"=> "As observações não podem exceder 5000 caracteres.",
            "expires_at.required"=> "A data de expiração é obrigatória.",
            "expires_at.date"=> "A data de expiração deve ser uma data válida.",
        ];
    }
}
