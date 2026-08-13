<?php

namespace App\Http\Requests\Sgc;

use App\Helpers\TextFormatter;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreProductRequest extends SgcFormRequest
{
    protected function prepareForValidation(): void
    {
        if( $this->has("name") ) {
            $this->merge([
                "name" => TextFormatter::all($this->input("name")),
            ]);
        }
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            "company_id" => [
                "required",
                "integer",
                Rule::exists("sgc_companies", "id"),
            ],
            "brand_id" => [
                "nullable",
                "integer",
                Rule::exists("sgc_brands", "id"),
            ],
            "product_category_id" => [
                "nullable",
                "integer",
                Rule::exists("sgc_product_categories", "id"),
            ],
            "name" => ["required", "string", "max:255"],
            "technical_specifications" => ["nullable", "string"],
            "notes" => ["nullable", "string"],
            "sale_price" => ["nullable", "numeric", "min:0", "decimal:0,2"],
            "cost_price" => ["nullable", "numeric", "min:0", "decimal:0,2"],
            "currency" => ["nullable", "string", "size:3", Rule::in(["BRL"])],
        ];
    }

    public function messages(): array
    {
        return [
            "company_id.required" => "A empresa é obrigatória.",
            "company_id.integer" => "A empresa deve ser um número inteiro.",
            "company_id.exists" => "A empresa selecionada é inválida.",
            "brand_id.integer" => "A marca deve ser um número inteiro.",
            "brand_id.exists" => "A marca selecionada é inválida.",
            "product_category_id.integer" => "A categoria deve ser um número inteiro.",
            "product_category_id.exists" => "A categoria selecionada é inválida.",
            "name.required" => "O nome é obrigatório.",
            "name.string" => "O nome deve ser um texto válido.",
            "name.max" => "O nome não pode ter mais de 255 caracteres.",
            "technical_specifications.string" => "As especificações técnicas devem ser um texto válido.",
            "notes.string" => "As observações devem ser um texto válido.",
            "sale_price.numeric" => "O preço de venda deve ser um número válido.",
            "sale_price.min" => "O preço de venda não pode ser negativo.",
            "sale_price.decimal" => "O preço de venda deve ter no máximo 2 casas decimais.",
            "cost_price.numeric" => "O preço de custo deve ser um número válido.",
            "cost_price.min" => "O preço de custo não pode ser negativo.",
            "cost_price.decimal" => "O preço de custo deve ter no máximo 2 casas decimais.",
            "currency.string" => "A moeda deve ser um texto válido.",
            "currency.size" => "A moeda deve ter exatamente 3 caracteres.",
            "currency.in" => "A moeda selecionada é inválida.",
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator) {
            if( !$this->filled("company_id") ) {
                return;
            }

            $company_id = $this->integer("company_id");

            if( !$this->companyAccessible($company_id) ) {
                $validator->errors()->add("company_id", "Empresa inválida ou sem permissão.");
            }

            if( $this->filled("notes") && !$this->companyCanManageNotes($company_id) ) {
                $validator->errors()->add("notes", "Apenas o proprietário ou administradores da empresa podem adicionar observações.");
            }
        });
    }
}
