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
trait BelongsToTenant
{
    protected static function bootBelongsToTenant(): void
    {
        static::addGlobalScope("tenant", function (Builder $builder) {
            $tenant_id = SGCContext::tenantId();

            if( $tenant_id ) {
                $builder->where("tenant_id", $tenant_id);
            } else {
                $builder->whereRaw("1 = 0");
            }
        });

        static::creating(function (Model $model) {
            if( empty($model->tenant_id) ) {
                $model->tenant_id = SGCContext::tenantId();
            }
        });
    }
}
