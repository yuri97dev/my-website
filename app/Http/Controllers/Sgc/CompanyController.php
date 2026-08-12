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
        $perPage = min($request->integer("per_page", 15), 100);

        $paginator = SgcCompany::query()
            ->accessibleBy($request->user())
            ->orderByDesc("id")
            ->paginate($perPage);

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

    public function show(Request $request, SgcCompany $sgcCompany)
    {
        abort_unless(
            $sgcCompany->isAccessibleBy($request->user()),
            Response::HTTP_FORBIDDEN
        );

        return response()->json([
            "company" => $sgcCompany,
        ], 200);
    }

    public function update(UpdateCompanyRequest $request, SgcCompany $sgcCompany)
    {
        abort_unless(
            $sgcCompany->isAccessibleBy($request->user()),
            Response::HTTP_FORBIDDEN
        );

        $sgcCompany->update($request->validated());

        return response()->json([
            "company" => $sgcCompany->fresh(),
        ], 200);
    }

    public function destroy(Request $request, SgcCompany $sgcCompany)
    {
        abort_unless(
            $sgcCompany->owner_id === $request->user()->id,
            Response::HTTP_FORBIDDEN
        );

        $sgcCompany->delete();

        return response()->json([
            "message" => "Empresa removida com sucesso.",
        ], 200);
    }
}
