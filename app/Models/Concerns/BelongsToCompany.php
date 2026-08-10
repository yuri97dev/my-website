<?php

namespace App\Models\Concerns;

use App\Helpers\SGCContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * @mixin Model
 *
 * @method static void addGlobalScope(string $identifier, \Closure $scope)
 * @method static void creating(\Closure $callback)
 */
trait BelongsToCompany
{
    protected static function bootBelongsToCompany(): void
    {
        static::addGlobalScope("company", function (Builder $builder) {
            $company_id = SGCContext::companyId();

            if( $company_id ) {
                $builder->where("company_id", $company_id);
            } else {
                $builder->whereRaw("1 = 0");
            }
        });

        static::creating(function (Model $model) {
            if( empty($model->company_id) ) {
                $model->company_id = SGCContext::companyId();
            }
        });
    }
}
