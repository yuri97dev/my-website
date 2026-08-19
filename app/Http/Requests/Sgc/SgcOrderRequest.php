<?php

namespace App\Http\Requests\Sgc;

use App\Models\SgcCustomerAddress;
use App\Models\SgcOrder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

abstract class SgcOrderRequest extends SgcFormRequest
{
    /**
     * @return array<string, mixed>
     */
    protected function orderRules(): array
    {
        return [
            "company_id" => [
                "required",
                "integer",
                Rule::exists("sgc_companies", "id"),
            ],
            "customer_id" => [
                "nullable",
                "integer",
                Rule::exists("sgc_customers", "id"),
            ],
            "customer_address_id" => [
                "nullable",
                "integer",
                Rule::exists("sgc_customer_addresses", "id"),
            ],
            "payment_method_id" => [
                "nullable",
                "integer",
                Rule::exists("sgc_payment_methods", "id"),
            ],
            "installments_count" => ["nullable", "integer", "min:1", "max:24"],
            "installments" => ["nullable", "array"],
            "installments.*.installment_number" => ["required", "integer", "min:1"],
            "installments.*.due_date" => ["nullable", "date"],
            "installments.*.amount" => ["required", "numeric", "min:0", "decimal:0,2"],
            "status" => [
                "nullable",
                "string",
                Rule::in(SgcOrder::STATUSES),
            ],
            "discount_amount" => ["nullable", "numeric", "min:0", "decimal:0,2"],
            "currency" => ["nullable", "string", "size:3", Rule::in(["BRL"])],
            "notes" => ["nullable", "string", "max:500"],
            "items" => ["nullable", "array"],
            "items.*.product_id" => [
                "required",
                "integer",
                Rule::exists("sgc_products", "id"),
            ],
            "items.*.unit_of_measure_id" => [
                "nullable",
                "integer",
                Rule::exists("sgc_units_of_measure", "id"),
            ],
            "items.*.quantity" => ["required", "numeric", "min:0.001", "decimal:0,3"],
            "items.*.unit_price" => ["required", "numeric", "min:0", "decimal:0,2"],
            "items.*.discount_amount" => ["nullable", "numeric", "min:0", "decimal:0,2"],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function orderMessages(): array
    {
        return [
            "company_id.required" => "A empresa é obrigatória.",
            "company_id.integer" => "A empresa deve ser um número inteiro.",
            "company_id.exists" => "A empresa selecionada é inválida.",
            "customer_id.integer" => "O cliente deve ser um número inteiro.",
            "customer_id.exists" => "O cliente selecionado é inválido.",
            "customer_address_id.integer" => "O endereço deve ser um número inteiro.",
            "customer_address_id.exists" => "O endereço selecionado é inválido.",
            "payment_method_id.integer" => "A forma de pagamento deve ser um número inteiro.",
            "payment_method_id.exists" => "A forma de pagamento selecionada é inválida.",
            "installments_count.integer" => "O número de parcelas deve ser um número inteiro.",
            "installments_count.min" => "O número de parcelas deve ser no mínimo 1.",
            "installments_count.max" => "O número de parcelas não pode ser maior que 24.",
            "installments.array" => "As parcelas devem ser uma lista válida.",
            "installments.*.installment_number.required" => "O número da parcela é obrigatório.",
            "installments.*.installment_number.integer" => "O número da parcela deve ser um número inteiro.",
            "installments.*.installment_number.min" => "O número da parcela deve ser no mínimo 1.",
            "installments.*.due_date.date" => "A data de vencimento deve ser uma data válida.",
            "installments.*.amount.required" => "O valor da parcela é obrigatório.",
            "installments.*.amount.numeric" => "O valor da parcela deve ser um número válido.",
            "installments.*.amount.min" => "O valor da parcela não pode ser negativo.",
            "installments.*.amount.decimal" => "O valor da parcela deve ter no máximo 2 casas decimais.",
            "status.string" => "O status deve ser um texto válido.",
            "status.in" => "O status selecionado é inválido.",
            "discount_amount.numeric" => "O desconto deve ser um número válido.",
            "discount_amount.min" => "O desconto não pode ser negativo.",
            "discount_amount.decimal" => "O desconto deve ter no máximo 2 casas decimais.",
            "currency.string" => "A moeda deve ser um texto válido.",
            "currency.size" => "A moeda deve ter exatamente 3 caracteres.",
            "currency.in" => "A moeda selecionada é inválida.",
            "notes.string" => "As observações devem ser um texto válido.",
            "notes.max" => "As observações não podem ter mais de 500 caracteres.",
            "items.array" => "Os itens devem ser uma lista válida.",
            "items.*.product_id.required" => "O produto é obrigatório.",
            "items.*.product_id.integer" => "O produto deve ser um número inteiro.",
            "items.*.product_id.exists" => "O produto selecionado é inválido.",
            "items.*.unit_of_measure_id.integer" => "A unidade de medida deve ser um número inteiro.",
            "items.*.unit_of_measure_id.exists" => "A unidade de medida selecionada é inválida.",
            "items.*.quantity.required" => "A quantidade é obrigatória.",
            "items.*.quantity.numeric" => "A quantidade deve ser um número válido.",
            "items.*.quantity.min" => "A quantidade deve ser maior que zero.",
            "items.*.quantity.decimal" => "A quantidade deve ter no máximo 3 casas decimais.",
            "items.*.unit_price.required" => "O preço unitário é obrigatório.",
            "items.*.unit_price.numeric" => "O preço unitário deve ser um número válido.",
            "items.*.unit_price.min" => "O preço unitário não pode ser negativo.",
            "items.*.unit_price.decimal" => "O preço unitário deve ter no máximo 2 casas decimais.",
            "items.*.discount_amount.numeric" => "O desconto do item deve ser um número válido.",
            "items.*.discount_amount.min" => "O desconto do item não pode ser negativo.",
            "items.*.discount_amount.decimal" => "O desconto do item deve ter no máximo 2 casas decimais.",
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

                return;
            }

            $this->validateOrderCompany($validator, $company_id);
            $this->validateCustomer($validator, $company_id);
            $this->validateCustomerAddress($validator);
            $this->validateItems($validator, $company_id);
            $this->validateInstallments($validator);
            $this->validateConfirmation($validator);
        });
    }

    protected function validateOrderCompany(Validator $validator, int $company_id): void
    {
        $order = $this->route("sgc_order");

        if( !($order instanceof SgcOrder) ) {
            return;
        }

        if( (int) $order->company_id !== $company_id ) {
            $validator->errors()->add("company_id", "Pedido não vinculado a esta empresa.");
        }
    }

    protected function validateCustomer(Validator $validator, int $company_id): void
    {
        if( !$this->filled("customer_id") ) {
            return;
        }

        $linked = DB::table("sgc_company_customer")
            ->where("company_id", $company_id)
            ->where("customer_id", $this->integer("customer_id"))
            ->exists();

        if( !$linked ) {
            $validator->errors()->add("customer_id", "Cliente não vinculado a esta empresa.");
        }
    }

    protected function validateCustomerAddress(Validator $validator): void
    {
        if( !$this->filled("customer_address_id") ) {
            return;
        }

        if( !$this->filled("customer_id") ) {
            $validator->errors()->add(
                "customer_address_id",
                "Selecione um cliente para informar o endereço."
            );

            return;
        }

        $belongs = SgcCustomerAddress::query()
            ->where("id", $this->integer("customer_address_id"))
            ->where("customer_id", $this->integer("customer_id"))
            ->exists();

        if( !$belongs ) {
            $validator->errors()->add(
                "customer_address_id",
                "Endereço não pertence ao cliente selecionado."
            );
        }
    }

    protected function validateItems(Validator $validator, int $company_id): void
    {
        foreach( $this->input("items", []) as $index => $item ) {
            $product_id = $item["product_id"] ?? null;

            if( !$product_id ) {
                continue;
            }

            $linked = DB::table("sgc_company_product")
                ->where("company_id", $company_id)
                ->where("product_id", $product_id)
                ->exists();

            if( !$linked ) {
                $validator->errors()->add(
                    "items.{$index}.product_id",
                    "Produto não vinculado a esta empresa."
                );
            }
        }
    }

    protected function validateInstallments(Validator $validator): void
    {
        $installments = $this->input("installments", []);

        if( count($installments) < 1 ) {
            return;
        }

        $numbers = [];

        foreach( $installments as $index => $installment ) {
            $number = (int) ($installment["installment_number"] ?? 0);

            if( in_array($number, $numbers, true) ) {
                $validator->errors()->add(
                    "installments.{$index}.installment_number",
                    "Número de parcela duplicado."
                );
            }

            $numbers[] = $number;
        }

        $items = $this->input("items", []);

        $order_discount = (float) ($this->input("discount_amount") ?? 0);
        $items_total = 0;

        foreach( $items as $item ) {
            $quantity = (float) ($item["quantity"] ?? 0);
            $unit_price = (float) ($item["unit_price"] ?? 0);
            $discount = (float) ($item["discount_amount"] ?? 0);

            $items_total += ($quantity * $unit_price) - $discount;
        }

        $expected_total = max(0, round($items_total - $order_discount, 2));
        $installments_total = 0;

        foreach( $installments as $installment ) {
            $installments_total += (float) ($installment["amount"] ?? 0);
        }

        $installments_total = round($installments_total, 2);

        if( $installments_total !== $expected_total ) {
            $validator->errors()->add(
                "installments",
                "A soma das parcelas deve ser igual ao total do pedido."
            );
        }
    }

    protected function validateConfirmation(Validator $validator): void
    {
        if( $this->input("status") !== SgcOrder::STATUS_CONFIRMED ) {
            return;
        }

        if( !$this->filled("customer_id") ) {
            $validator->errors()->add("customer_id", "O cliente é obrigatório para confirmar o pedido.");
        }

        if( count($this->input("items", [])) < 1 ) {
            $validator->errors()->add("items", "Adicione ao menos um item para confirmar o pedido.");
        }
    }
}
