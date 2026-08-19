<?php

namespace App\Http\Requests\Sgc;

use App\Models\SgcOrder;
use App\Models\SgcOrderInstallment;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Validator;

class PayOrderInstallmentRequest extends SgcFormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            "amount" => ["required", "numeric", "min:0.01", "decimal:0,2"],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            "amount.required" => "O valor da baixa é obrigatório.",
            "amount.numeric" => "O valor da baixa deve ser um número válido.",
            "amount.min" => "O valor da baixa deve ser maior que zero.",
            "amount.decimal" => "O valor da baixa deve ter no máximo 2 casas decimais.",
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator) {
            $order = $this->route("sgc_order");
            $installment = $this->route("sgc_order_installment");

            if( !($order instanceof SgcOrder) || !$order->isAccessibleBy($this->user()) ) {
                $validator->errors()->add("order", "Pedido inválido ou sem permissão.");

                return;
            }

            if( $order->isCancelled() ) {
                $validator->errors()->add("order", "Não é possível dar baixa em um pedido cancelado.");

                return;
            }

            if( !$order->isConfirmed() ) {
                $validator->errors()->add("order", "Só é possível dar baixa em pedidos confirmados.");

                return;
            }

            if(
                !($installment instanceof SgcOrderInstallment)
                || (int) $installment->order_id !== (int) $order->id
            ) {
                $validator->errors()->add("installment", "Parcela inválida para este pedido.");

                return;
            }

            $remaining = $installment->remainingAmount();

            if( $remaining <= 0 ) {
                $validator->errors()->add("amount", "Esta parcela já está quitada.");

                return;
            }

            $amount = round((float) $this->input("amount"), 2);

            if( $amount > $remaining ) {
                $validator->errors()->add(
                    "amount",
                    "O valor da baixa não pode ser maior que o restante da parcela."
                );
            }
        });
    }
}
