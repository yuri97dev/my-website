<?php

namespace App\Http\Controllers\Sgc;

use App\Http\Controllers\Controller;
use App\Models\SgcCompany;
use App\Services\Sgc\ProductListingService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CompanyProductController extends Controller
{
    public function __construct(
        private ProductListingService $listing,
    ) {}

    public function index(Request $request)
    {
        $request->validate([
            "company_id" => ["required", "integer", "exists:sgc_companies,id"],
            "product_id" => ["nullable", "integer"],
            "id" => ["nullable", "integer"],
            "name" => ["nullable", "string", "max:255"],
            "search" => ["nullable", "string", "max:255"],
        ]);

        $company = SgcCompany::query()->findOrFail($request->integer("company_id"));

        abort_unless(
            $company->isAccessibleBy($request->user()),
            Response::HTTP_FORBIDDEN
        );

        $paginator = $this->listing->companyProducts($request, $company);

        return response()->json([
            "current_page" => $paginator->currentPage(),
            "last_page" => $paginator->lastPage(),
            "per_page" => $paginator->perPage(),
            "total" => $paginator->total(),
            "company_products" => $paginator->items(),
        ], 200);
    }
}
