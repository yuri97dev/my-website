<?php

namespace App\Http\Requests\ArticleSource;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\ArticleSource;

class TestArticleSourceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->isSystemAdmin();
    }

    protected function prepareForValidation(): void
    {
        if( $this->has("http_method") ) {
            $this->merge([
                "http_method" => strtoupper(trim($this->input("http_method"))),
            ]);
        }
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            "driver" => [
                "required",
                "string",
                Rule::in([
                    ArticleSource::DRIVER_RSS,
                    ArticleSource::DRIVER_BROWSER,
                ]),
            ],
            "http_method" => ["required", "string", "max:10"],
            "list_url" => ["required", "string", "max:255"],
            "list_item_selector" => ["required", "string", "max:255"],
            "item_title_selector" => ["required", "string", "max:255"],
            "item_link_selector" => ["required", "string", "max:255"],
            "content_selector" => ["required", "string", "max:255"],
            "user_agent" => ["nullable", "string", "max:500"],
            "proxy_server" => ["nullable", "string", "max:255"],
            "accept_language" => ["nullable", "string", "max:100"],
        ];
    }

    public function messages(): array
    {
        return [
            "driver.required" => "O driver é obrigatório.",
            "driver.in" => "O driver deve ser rss ou browser.",
            "http_method.required" => "O método HTTP é obrigatório.",
            "list_url.required" => "A URL da listagem é obrigatória.",
            "list_item_selector.required" => "O seletor de item da listagem é obrigatório.",
            "item_title_selector.required" => "O seletor de título é obrigatório.",
            "item_link_selector.required" => "O seletor de link é obrigatório.",
            "content_selector.required" => "O seletor de conteúdo é obrigatório.",
        ];
    }
}
