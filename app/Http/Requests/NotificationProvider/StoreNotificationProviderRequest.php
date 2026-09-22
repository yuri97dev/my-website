<?php

namespace App\Http\Requests\NotificationProvider;

use App\Helpers\TextFormatter;
use App\Support\NotificationProviderConfigRules;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StoreNotificationProviderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->isSystemAdmin();
    }

    protected function prepareForValidation(): void
    {
        $merge = [];

        if( $this->has("name") ) {
            $merge["name"] = TextFormatter::removeExtraSpaces($this->input("name"));
        }

        if( $this->filled("key") ) {
            $merge["key"] = Str::slug($this->input("key"));
        }

        if( $this->filled("channel") ) {
            $merge["channel"] = Str::slug($this->input("channel"));
        }

        $this->merge($merge);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $channel = (string) $this->input("channel");

        return [
            "key" => [
                "required",
                "string",
                "max:100",
                Rule::unique("notification_providers", "key")
                    ->where("channel", $channel),
            ],
            "channel" => NotificationProviderConfigRules::channel(),
            "name" => ["required", "string", "max:100"],
            "is_enabled" => ["sometimes", "boolean"],
            ...NotificationProviderConfigRules::forChannel($channel, creating: true),
        ];
    }

    public function messages(): array
    {
        return [
            "key.required" => "A chave é obrigatória.",
            "key.unique" => "Esta chave já está em uso para o canal selecionado.",
            "channel.required" => "O canal é obrigatório.",
            "channel.in" => "Canal de notificação inválido.",
            "name.required" => "O nome é obrigatório.",
            "config.required" => "A configuração é obrigatória.",
            "config.webhook_url.required" => "A URL do webhook é obrigatória.",
            "config.webhook_url.url" => "Informe uma URL de webhook válida.",
        ];
    }
}
