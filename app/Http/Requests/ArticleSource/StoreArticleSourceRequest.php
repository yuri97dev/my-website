<?php

namespace App\Http\Requests\ArticleSource;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\ArticleSource;
use App\Helpers\TextFormatter;

class StoreArticleSourceRequest extends FormRequest
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

        if( $this->has("locale") ) {
            $merge["locale"] = strtolower(trim($this->input("locale")));
        }

        if( $this->has("http_method") ) {
            $merge["http_method"] = strtoupper(trim($this->input("http_method")));
        }

        $this->merge($merge);
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
                    ArticleSource::DRIVER_JSON,
                ]),
            ],
            "name" => [
                "required",
                "string",
                "max:100",
                Rule::unique("article_sources", "name"),
            ],
            "locale" => ["sometimes", "required", "string", "max:10"],
            "http_method" => ["sometimes", "required", "string", "max:10"],
            "list_url" => [
                "required",
                "string",
                "max:255",
                Rule::unique("article_sources", "list_url"),
            ],
            "list_item_selector" => ["required", "string", "max:255"],
            "item_title_selector" => ["required", "string", "max:255"],
            "item_link_selector" => ["required", "string", "max:255"],
            "item_link_base_url" => ["nullable", "string", "max:255"],
            "content_selector" => ["required", "string", "max:255"],
            "user_agent" => ["nullable", "string", "max:500"],
            "proxy_server" => ["nullable", "string", "max:255"],
            "accept_language" => ["nullable", "string", "max:100"],
            "is_enabled" => ["sometimes", "boolean"],
        ];
    }

    public function messages(): array
    {
        return [
            "driver.required" => "O driver é obrigatório.",
            "driver.in" => "O driver deve ser rss, browser ou json.",
            "name.required" => "O nome é obrigatório.",
            "name.max" => "O nome não pode ter mais de 100 caracteres.",
            "name.unique" => "Este nome já está em uso.",
            "locale.required" => "O locale é obrigatório.",
            "locale.max" => "O locale não pode ter mais de 10 caracteres.",
            "http_method.required" => "O método HTTP é obrigatório.",
            "http_method.max" => "O método HTTP não pode ter mais de 10 caracteres.",
            "list_url.required" => "A URL da listagem é obrigatória.",
            "list_url.unique" => "Esta URL da listagem já está em uso.",
            "list_item_selector.required" => "O seletor de item da listagem é obrigatório.",
            "item_title_selector.required" => "O seletor de título é obrigatório.",
            "item_link_selector.required" => "O seletor de link é obrigatório.",
            "content_selector.required" => "O seletor de conteúdo é obrigatório.",
            "is_enabled.boolean" => "O campo habilitado deve ser verdadeiro ou falso.",
        ];
    }
}
