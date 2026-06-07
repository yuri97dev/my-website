<?php

namespace App\Http\Requests\Account;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Helpers\TextFormatter;

class UpdatePersonalDataRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return (bool) $this->user();
    }

    protected function prepareForValidation(): void
    {
        $parsed_name = TextFormatter::all($this->input("name"));
        $parsed_email = TextFormatter::all($this->input("email"));

        $this->merge([
            "name" => $parsed_name,
            "email" => mb_strtolower($parsed_email),
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            "name" => [
                "required",
                "string",
                "max:255",
                "regex:/^[\p{L}'\-.]+\s+[\p{L}'\-.]+/u",
            ],
            "email" => [
                "required",
                "email",
                Rule::unique("users", "email")->ignore($this->user()->id),
            ],
            "accepts_marketing" => "nullable|boolean",
        ];
    }

    public function messages(): array
    {
        return [
            "name.required" => "O nome é obrigatório.",
            "name.string" => "O nome deve ser um texto válido.",
            "name.max" => "O nome não pode ter mais de 255 caracteres.",
            "name.regex" => "Informe o nome completo, incluindo pelo menos um sobrenome.",
            "email.required" => "O e-mail é obrigatório.",
            "email.email" => "Informe um e-mail válido.",
            "email.unique" => "Este e-mail já está em uso.",
            "accepts_marketing.boolean" => "O valor de aceite de marketing deve ser verdadeiro ou falso.",
        ];
    }
}
