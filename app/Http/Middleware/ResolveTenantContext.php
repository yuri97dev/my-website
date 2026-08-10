<?php

namespace App\Http\Middleware;

use App\Helpers\SGCContext;
use App\Models\SGC\Company;
use App\Models\SGC\Tenant;
use App\Models\SGC\TenantMember;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveTenantContext
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if( $user === null ) {
            abort(401);
        }

        if( !$request->session()->has("sgc_tenant_id") || !$request->session()->has("sgc_company_id") ) {
            abort(403);
        }

        $tenant_id = (int) $request->session()->get("sgc_tenant_id");
        $company_id = (int) $request->session()->get("sgc_company_id");

        $is_member = TenantMember::query()
            ->where("tenant_id", $tenant_id)
            ->where("user_id", $user->id)
            ->exists();

        if( !$is_member ) {
            abort(403);
        }

        $company = Company::query()
            ->where("id", $company_id)
            ->where("tenant_id", $tenant_id)
            ->first();

        if( $company === null ) {
            abort(403);
        }

        $tenant = Tenant::query()->findOrFail($tenant_id);

        SGCContext::set($tenant_id, $company_id);

        $request->attributes->set("tenant", $tenant);
        $request->attributes->set("SGCCompany", $company);

        try {
            return $next($request);
        } finally {
            SGCContext::forget();
        }
    }
}
