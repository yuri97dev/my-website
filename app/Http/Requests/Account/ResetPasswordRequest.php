<?php

namespace App\Http\Requests\Account;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use App\Helpers\TextFormatter;

class ResetPasswordRequest extends FormRequest
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
        $parsed_email = TextFormatter::all($this->input("email"));

        $this->merge([
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
            "token" => "required|string",
            "email" => "required|email",
            "password" => "required|string|min:6",
            "password_confirmation" => "required|string|min:6|same:password",
        ];
    }

    public function messages(): array
    {
        return [
            "token.required" => "O token de redefinição é obrigatório.",
            "token.string" => "O token de redefinição deve ser um texto válido.",
            "email.required" => "O e-mail é obrigatório.",
            "email.email" => "Informe um e-mail válido.",
            "password.required" => "A senha é obrigatória.",
            "password.string" => "A senha deve ser um texto válido.",
            "password.min" => "A senha deve ter no mínimo 6 caracteres.",
            "password_confirmation.required" => "A confirmação da senha é obrigatória.",
            "password_confirmation.string" => "A confirmação da senha deve ser um texto válido.",
            "password_confirmation.min" => "A confirmação da senha deve ter no mínimo 6 caracteres.",
            "password_confirmation.same" => "A confirmação da senha não confere.",
        ];
    }
}
