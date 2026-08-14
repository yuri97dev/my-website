<?php

namespace App\Http\Requests\Sgc;

use App\Models\SgcCompany;
use Illuminate\Foundation\Http\FormRequest;

abstract class SgcFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user();
    }

    protected function companyAccessible(int $company_id): bool
    {
        $company = SgcCompany::query()->find($company_id);

        if( !$company ) {
            return false;
        }

        return $company->isAccessibleBy($this->user());
    }

    protected function companyOwnedBy(int $company_id): bool
    {
        $company = SgcCompany::query()->find($company_id);

        if( !$company ) {
            return false;
        }

        return $company->owner_id === $this->user()->id;
    }

    protected function companyCanBeManagedBy(int $company_id): bool
    {
        $company = SgcCompany::query()->find($company_id);

        if( !$company ) {
            return false;
        }

        return $company->canBeManagedBy($this->user());
    }
}
