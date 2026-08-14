<?php

namespace App\Http\Requests\Sgc;

use App\Helpers\TextFormatter;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\DB;
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

        if( $this->has("serial_number") ) {
            $serial_number = trim($this->input("serial_number"));

            $this->merge([
                "serial_number" => $serial_number === "" ? null : $serial_number,
            ]);
        }
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $product = $this->route("sgc_product");
        $company_id = $this->integer("company_id");

        $pivot_id = $product
            ? DB::table("sgc_company_product")
                ->where("company_id", $company_id)
                ->where("product_id", $product->id)
                ->value("id")
            : null;

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
            "sale_price" => ["nullable", "numeric", "min:0", "decimal:0,2"],
            "cost_price" => ["nullable", "numeric", "min:0", "decimal:0,2"],
            "stock_quantity" => ["nullable", "numeric", "min:0", "decimal:0,3"],
            "unit_of_measure_id" => [
                "nullable",
                "integer",
                Rule::exists("sgc_units_of_measure", "id"),
            ],
            "serial_number" => [
                "nullable",
                "string",
                "max:64",
                Rule::unique("sgc_company_product", "serial_number")
                    ->where(fn ($query) => $query->where("product_id", $product->id))
                    ->ignore($pivot_id, "id"),
            ],
            "currency" => ["nullable", "string", "size:3", Rule::in(["BRL"])],
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

            if( $this->filled("notes") && !$this->companyCanBeManagedBy($company_id) ) {
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
            "sale_price.numeric" => "O preço de venda deve ser um número válido.",
            "sale_price.min" => "O preço de venda não pode ser negativo.",
            "sale_price.decimal" => "O preço de venda deve ter no máximo 2 casas decimais.",
            "cost_price.numeric" => "O preço de custo deve ser um número válido.",
            "cost_price.min" => "O preço de custo não pode ser negativo.",
            "cost_price.decimal" => "O preço de custo deve ter no máximo 2 casas decimais.",
            "stock_quantity.numeric" => "A quantidade em estoque deve ser um número válido.",
            "stock_quantity.min" => "A quantidade em estoque não pode ser negativa.",
            "stock_quantity.decimal" => "A quantidade em estoque deve ter no máximo 3 casas decimais.",
            "unit_of_measure_id.integer" => "A unidade de medida deve ser um número inteiro.",
            "unit_of_measure_id.exists" => "A unidade de medida selecionada é inválida.",
            "serial_number.string" => "O número de série deve ser um texto válido.",
            "serial_number.max" => "O número de série não pode ter mais de 64 caracteres.",
            "serial_number.unique" => "Este número de série já está em uso para este produto.",
            "currency.string" => "A moeda deve ser um texto válido.",
            "currency.size" => "A moeda deve ter exatamente 3 caracteres.",
            "currency.in" => "A moeda selecionada é inválida.",
        ];
    }
}
