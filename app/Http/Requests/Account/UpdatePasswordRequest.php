<?php

namespace App\Http\Requests\Account;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdatePasswordRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return (bool) $this->user();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            "current_password" => ["required", "string", "current_password"],
            "password" => "required|string|min:6",
            "password_confirmation" => "required|string|min:6|same:password",
        ];
    }

    public function messages(): array
    {
        return [
            "current_password.required" => "A senha atual é obrigatória.",
            "current_password.string" => "A senha atual deve ser um texto válido.",
            "current_password.current_password" => "A senha atual está incorreta.",
            "password.required" => "A nova senha é obrigatória.",
            "password.string" => "A nova senha deve ser um texto válido.",
            "password.min" => "A nova senha deve ter no mínimo 6 caracteres.",
            "password_confirmation.required" => "A confirmação da senha é obrigatória.",
            "password_confirmation.string" => "A confirmação da senha deve ser um texto válido.",
            "password_confirmation.min" => "A confirmação da senha deve ter no mínimo 6 caracteres.",
            "password_confirmation.same" => "A confirmação da senha não confere.",
        ];
    }
}
