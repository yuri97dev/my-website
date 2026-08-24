<?php

namespace App\Http\Requests\ScrapedArticle;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class BulkDeleteScrapedArticlesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->isSystemAdmin();
    }

    protected function prepareForValidation(): void
    {
        if( $this->has("untranslated_only") ) {
            $this->merge([
                "untranslated_only" => $this->boolean("untranslated_only"),
            ]);
        }
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            "article_source_id" => ["nullable", "integer", "exists:article_sources,id"],
            "date_from" => ["nullable", "date"],
            "date_to" => ["nullable", "date", "after_or_equal:date_from"],
            "untranslated_only" => ["sometimes", "boolean"],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if( $this->filled("date_to") && !$this->filled("date_from") ) {
                $validator->errors()->add(
                    "date_to",
                    "Informe a data inicial antes da data final.",
                );
            }

            if( $this->hasAnyFilter() ) return;

            $validator->errors()->add(
                "filters",
                "Informe ao menos um filtro para excluir artigos.",
            );
        });
    }

    public function hasAnyFilter(): bool
    {
        return $this->integer("article_source_id")
            || $this->filled("date_from")
            || $this->boolean("untranslated_only");
    }

    public function messages(): array
    {
        return [
            "article_source_id.integer" => "A fonte selecionada é inválida.",
            "article_source_id.exists" => "A fonte selecionada não existe.",
            "date_from.date" => "A data inicial é inválida.",
            "date_to.date" => "A data final é inválida.",
            "date_to.after_or_equal" => "A data final deve ser igual ou posterior à data inicial.",
            "untranslated_only.boolean" => "O filtro de artigos não traduzidos é inválido.",
        ];
    }
}
