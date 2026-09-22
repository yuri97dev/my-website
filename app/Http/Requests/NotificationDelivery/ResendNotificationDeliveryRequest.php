<?php

namespace App\Http\Requests\NotificationDelivery;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use App\Helpers\TextFormatter;

class ResendNotificationDeliveryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->isSystemAdmin();
    }

    protected function prepareForValidation(): void
    {
        $merge = [];

        if( $this->has("subject") ) {
            $merge["subject"] = TextFormatter::removeExtraSpaces($this->input("subject"));
        }

        if( $this->has("body") ) {
            $merge["body"] = trim((string) $this->input("body"));
        }

        $this->merge($merge);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            "subject" => ["nullable", "string", "max:256"],
            "body" => ["nullable", "string", "max:5000"],
        ];
    }

    public function messages(): array
    {
        return [
            "subject.max" => "O assunto não pode ter mais de 256 caracteres.",
            "body.max" => "O corpo não pode ter mais de 5000 caracteres.",
        ];
    }
}
