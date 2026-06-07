<?php

namespace App\Http\Requests\Auth;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use App\Helpers\TextFormatter;

class RegisterRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
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
            "email" => "required|email|unique:users,email",
            "password" => "required|string|min:6",
            "password_confirmation" => "required|string|min:6|same:password",
            "accepts_marketing" => "nullable|boolean",
            "accepted_terms" => "required|accepted",
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
            "email.unique" => "Não foi possível processar sua solicitação.",
            "password.required" => "A senha é obrigatória.",
            "password.string" => "A senha deve ser um texto válido.",
            "password.min" => "A senha deve ter no mínimo 6 caracteres.",
            "password_confirmation.required" => "A confirmação da senha é obrigatória.",
            "password_confirmation.string" => "A confirmação da senha deve ser um texto válido.",
            "password_confirmation.min" => "A confirmação da senha deve ter no mínimo 6 caracteres.",
            "password_confirmation.same" => "A confirmação da senha não confere.",
            "accepts_marketing.boolean" => "O valor de aceite de marketing deve ser verdadeiro ou falso.",
            "accepted_terms.required" => "O aceite dos termos é obrigatório.",
            "accepted_terms.accepted" => "Você deve aceitar os termos de uso.",
        ];
    }
}
