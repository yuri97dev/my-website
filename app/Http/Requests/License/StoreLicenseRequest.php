<?php

namespace App\Http\Requests\License;

use App\Helpers\LicenseLimit;
use App\Models\License;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreLicenseRequest extends FormRequest
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
            "expires_at"=> ["required", "date", "after_or_equal:today"],
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
            "expires_at.after_or_equal"=> "A data de expiração não pode ser anterior a hoje.",
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator) {
            $limit = LicenseLimit::perUser();

            $used = License::query()
                ->accessibleBy($this->user())
                ->where("status", "!=", License::STATUS_REVOKED)
                ->count();

            if( $used >= $limit ) {
                $validator->errors()->add(
                    "label",
                    "Limite de {$limit} licenças atingido. Revogue ou remova uma licença para criar outra."
                );
            }
        });
    }
}
