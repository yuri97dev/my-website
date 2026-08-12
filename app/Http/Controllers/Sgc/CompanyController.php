<?php

namespace App\Http\Controllers\Sgc;

use App\Http\Controllers\Controller;
use App\Http\Requests\Sgc\StoreCompanyRequest;
use App\Http\Requests\Sgc\UpdateCompanyRequest;
use App\Models\SgcCompany;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CompanyController extends Controller
{
    public function index(Request $request)
    {
        $per_page = min($request->integer("per_page", 15), 100);

        $paginator = SgcCompany::query()
            ->accessibleBy($request->user())
            ->orderByDesc("id")
            ->paginate($per_page);

        return response()->json([
            "current_page" => $paginator->currentPage(),
            "last_page" => $paginator->lastPage(),
            "per_page" => $paginator->perPage(),
            "total" => $paginator->total(),
            "companies" => $paginator->items(),
        ], 200);
    }

    public function store(StoreCompanyRequest $request)
    {
        $company = SgcCompany::create([
            ...$request->safe()->except("parent_id"),
            "owner_id" => $request->user()->id,
            "parent_id" => $request->input("parent_id"),
        ]);

        $company->users()->attach($request->user()->id, [
            "role" => SgcCompany::ROLE_ADMIN,
        ]);

        return response()->json([
            "company" => $company,
        ], 201);
    }

    public function show(Request $request, SgcCompany $sgc_company)
    {
        abort_unless(
            $sgc_company->isAccessibleBy($request->user()),
            Response::HTTP_FORBIDDEN
        );

        return response()->json([
            "company" => $sgc_company,
        ], 200);
    }

    public function update(UpdateCompanyRequest $request, SgcCompany $sgc_company)
    {
        abort_unless(
            $sgc_company->isAccessibleBy($request->user()),
            Response::HTTP_FORBIDDEN
        );

        $sgc_company->update($request->validated());

        return response()->json([
            "company" => $sgc_company->fresh(),
        ], 200);
    }

    public function destroy(Request $request, SgcCompany $sgc_company)
    {
        abort_unless(
            $sgc_company->owner_id === $request->user()->id,
            Response::HTTP_FORBIDDEN
        );

        $sgc_company->delete();

        return response()->json([
            "message" => "Empresa removida com sucesso.",
        ], 200);
    }
}
