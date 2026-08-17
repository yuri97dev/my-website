<?php

namespace App\Http\Requests\Sgc;

use Illuminate\Contracts\Validation\ValidationRule;

class StoreOrderRequest extends SgcOrderRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return $this->orderRules();
    }

    public function messages(): array
    {
        return $this->orderMessages();
    }
}
