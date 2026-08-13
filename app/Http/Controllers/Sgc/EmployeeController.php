<?php

namespace App\Http\Controllers\Sgc;

use App\Http\Controllers\Controller;
use App\Http\Requests\Sgc\StoreEmployeeRequest;
use App\Http\Requests\Sgc\UpdateEmployeeRequest;
use App\Models\SgcCompany;
use App\Models\SgcCompanyUser;
use App\Models\User;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EmployeeController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            "company_id" => ["nullable", "integer", "exists:sgc_companies,id"],
            "id" => ["nullable", "integer"],
            "name" => ["nullable", "string", "max:255"],
            "email" => ["nullable", "string", "max:255"],
            "role" => ["nullable", "string", "max:255"],
            "search" => ["nullable", "string", "max:255"],
        ]);

        if( $request->filled("company_id") ) {
            $company = SgcCompany::query()->findOrFail($request->integer("company_id"));

            abort_unless(
                $company->isAccessibleBy($request->user()),
                Response::HTTP_FORBIDDEN
            );
        }

        $per_page = min($request->integer("per_page", 15), 100);

        $paginator = SgcCompanyUser::query()
            ->whereHas("company", function ($query) use ($request) {
                $query->accessibleBy($request->user());

                if( $request->filled("company_id") ) {
                    $query->where("sgc_companies.id", $request->integer("company_id"));
                }
            })
            ->when($request->filled("id"), function ($query) use ($request) {
                $query->where("sgc_company_user.id", $request->integer("id"));
            })
            ->when($request->filled("role"), function ($query) use ($request) {
                $query->where("role", $request->input("role"));
            })
            ->when($request->filled("name"), function ($query) use ($request) {
                $term = "%".$request->input("name")."%";

                $query->whereHas("user", function ($query) use ($term) {
                    $query->where("name", "ilike", $term);
                });
            })
            ->when($request->filled("email"), function ($query) use ($request) {
                $term = "%".$request->input("email")."%";

                $query->whereHas("user", function ($query) use ($term) {
                    $query->where("email", "ilike", $term);
                });
            })
            ->when($request->filled("search"), function ($query) use ($request) {
                $term = "%".$request->input("search")."%";

                $query->where(function ($query) use ($term) {
                    $query->whereHas("user", function ($query) use ($term) {
                        $query->where("name", "ilike", $term)
                            ->orWhere("email", "ilike", $term);
                    })->orWhere("role", "ilike", $term);
                });
            })
            ->with([
                "user:id,name,email",
                "company:id,legal_name,trade_name,owner_id",
            ])
            ->orderByDesc("sgc_company_user.id")
            ->paginate($per_page);

        return response()->json([
            "current_page" => $paginator->currentPage(),
            "last_page" => $paginator->lastPage(),
            "per_page" => $paginator->perPage(),
            "total" => $paginator->total(),
            "employees" => $paginator->items(),
        ], 200);
    }

    public function store(StoreEmployeeRequest $request)
    {
        $validated = $request->validated();
        $user = User::query()->where("email", $validated["email"])->firstOrFail();

        $employee = SgcCompanyUser::create([
            "company_id" => $validated["company_id"],
            "user_id" => $user->id,
            "role" => $validated["role"],
        ]);

        $employee->load([
            "user:id,name,email",
            "company:id,legal_name,trade_name,owner_id",
        ]);

        return response()->json([
            "employee" => $employee,
        ], 201);
    }

    public function show(Request $request, SgcCompanyUser $sgc_company_user)
    {
        abort_unless(
            $sgc_company_user->company?->isAccessibleBy($request->user()),
            Response::HTTP_FORBIDDEN
        );

        $sgc_company_user->load([
            "user:id,name,email",
            "company:id,legal_name,trade_name,owner_id",
        ]);

        return response()->json([
            "employee" => $sgc_company_user,
        ], 200);
    }

    public function update(UpdateEmployeeRequest $request, SgcCompanyUser $sgc_company_user)
    {
        $validated = $request->validated();

        $sgc_company_user->update([
            "role" => $validated["role"],
        ]);

        $sgc_company_user->load([
            "user:id,name,email",
            "company:id,legal_name,trade_name,owner_id",
        ]);

        return response()->json([
            "employee" => $sgc_company_user,
        ], 200);
    }

    public function destroy(Request $request, SgcCompanyUser $sgc_company_user)
    {
        abort_unless(
            $sgc_company_user->company?->isAccessibleBy($request->user()),
            Response::HTTP_FORBIDDEN
        );

        abort_if(
            $sgc_company_user->isOwner(),
            Response::HTTP_FORBIDDEN,
            "O proprietário da empresa não pode ser removido."
        );

        $company = $sgc_company_user->company;

        abort_unless(
            $company && (
                $company->owner_id === $request->user()->id
                || $company->users()
                    ->where("users.id", $request->user()->id)
                    ->wherePivot("role", SgcCompany::ROLE_ADMIN)
                    ->exists()
            ),
            Response::HTTP_FORBIDDEN
        );

        $sgc_company_user->delete();

        return response()->json([
            "message" => "Funcionário removido com sucesso.",
        ], 200);
    }
}
