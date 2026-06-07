<?php

namespace App\Http\Requests\Account;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class DeleteAccountRequest extends FormRequest
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
        ];
    }

    public function messages(): array
    {
        return [
            "current_password.required" => "A senha é obrigatória.",
            "current_password.string" => "A senha deve ser um texto válido.",
            "current_password.current_password" => "A senha está incorreta.",
        ];
    }
}
