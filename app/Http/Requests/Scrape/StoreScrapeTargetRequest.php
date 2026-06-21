<?php

namespace App\Http\Requests\Scrape;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\ScrapeTarget;
use App\Helpers\TextFormatter;

class StoreScrapeTargetRequest extends FormRequest
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
                    ScrapeTarget::DRIVER_RSS,
                    ScrapeTarget::DRIVER_BROWSER,
                ]),
            ],
            "name" => [
                "required",
                "string",
                "max:100",
                Rule::unique("scrape_targets", "name"),
            ],
            "locale" => ["sometimes", "required", "string", "max:10"],
            "http_method" => ["sometimes", "required", "string", "max:10"],
            "list_url" => [
                "required",
                "string",
                "max:255",
                Rule::unique("scrape_targets", "list_url"),
            ],
            "list_item_selector" => ["required", "string", "max:255"],
            "item_title_selector" => ["required", "string", "max:255"],
            "item_link_selector" => ["required", "string", "max:255"],
            "content_selector" => ["required", "string", "max:255"],
            "is_enabled" => ["sometimes", "boolean"],
        ];
    }

    public function messages(): array
    {
        return [
            "driver.required" => "O driver é obrigatório.",
            "driver.in" => "O driver deve ser rss ou browser.",
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
