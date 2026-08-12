<?php

namespace App\Http\Requests\Sgc;

use App\Helpers\TextFormatter;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateProductRequest extends SgcFormRequest
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
            "name" => ["sometimes", "required", "string", "max:255"],
            "company_id" => [
                "required",
                "integer",
                Rule::exists("sgc_companies", "id"),
            ],
            "technical_specifications" => ["nullable", "string"],
            "notes" => ["nullable", "string"],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator) {
            $company_id = $this->integer("company_id");

            if( !$this->companyAccessible($company_id) ) {
                $validator->errors()->add("company_id", "Empresa inválida ou sem permissão.");
            }

            $product = $this->route("sgc_product");

            if( $product && !$product->companies()->where("sgc_companies.id", $company_id)->exists() ) {
                $validator->errors()->add("company_id", "Produto não vinculado a esta empresa.");
            }

            if( $this->filled("notes") && !$this->companyCanManageNotes($company_id) ) {
                $validator->errors()->add("notes", "Apenas o proprietário ou administradores da empresa podem alterar observações.");
            }
        });
    }

    public function messages(): array
    {
        return [
            "brand_id.integer" => "A marca deve ser um número inteiro.",
            "brand_id.exists" => "A marca selecionada é inválida.",
            "product_category_id.integer" => "A categoria deve ser um número inteiro.",
            "product_category_id.exists" => "A categoria selecionada é inválida.",
            "name.required" => "O nome é obrigatório.",
            "name.string" => "O nome deve ser um texto válido.",
            "name.max" => "O nome não pode ter mais de 255 caracteres.",
            "company_id.required" => "A empresa é obrigatória.",
            "company_id.integer" => "A empresa deve ser um número inteiro.",
            "company_id.exists" => "A empresa selecionada é inválida.",
            "technical_specifications.string" => "As especificações técnicas devem ser um texto válido.",
            "notes.string" => "As observações devem ser um texto válido.",
        ];
    }
}
