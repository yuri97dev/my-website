<?php

namespace App\Http\Controllers\Sgc;

use App\Http\Controllers\Controller;
use App\Http\Requests\Sgc\StoreCustomerRequest;
use App\Http\Requests\Sgc\UpdateCustomerRequest;
use App\Models\SgcCompany;
use App\Models\SgcCustomer;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            "company_id" => ["nullable", "integer", "exists:sgc_companies,id"],
            "id" => ["nullable", "integer"],
            "name" => ["nullable", "string", "max:255"],
            "nickname" => ["nullable", "string", "max:255"],
            "tax_id" => ["nullable", "string", "max:255"],
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

        $paginator = SgcCustomer::query()
            ->whereHas("companies", function ($query) use ($request) {
                $query->accessibleBy($request->user());

                if( $request->filled("company_id") ) {
                    $query->where("sgc_companies.id", $request->integer("company_id"));
                }
            })
            ->when($request->filled("id"), function ($query) use ($request) {
                $query->where("sgc_customers.id", $request->integer("id"));
            })
            ->when($request->filled("name"), function ($query) use ($request) {
                $query->where("name", "ilike", "%".$request->input("name")."%");
            })
            ->when($request->filled("nickname"), function ($query) use ($request) {
                $query->where("nickname", "ilike", "%".$request->input("nickname")."%");
            })
            ->when($request->filled("tax_id"), function ($query) use ($request) {
                $query->where("tax_id", "ilike", "%".$request->input("tax_id")."%");
            })
            ->when($request->filled("search"), function ($query) use ($request) {
                $term = "%".$request->input("search")."%";

                $query->where(function ($query) use ($term) {
                    $query->where("name", "ilike", $term)
                        ->orWhere("nickname", "ilike", $term)
                        ->orWhere("tax_id", "ilike", $term);
                });
            })
            ->with("addresses")
            ->orderByDesc("sgc_customers.id")
            ->paginate($per_page);

        return response()->json([
            "current_page" => $paginator->currentPage(),
            "last_page" => $paginator->lastPage(),
            "per_page" => $paginator->perPage(),
            "total" => $paginator->total(),
            "customers" => $paginator->items(),
        ], 200);
    }

    public function store(StoreCustomerRequest $request)
    {
        $validated = $request->validated();
        $company_id = $validated["company_id"];

        $customer = SgcCustomer::create(collect($validated)->except([
            "company_id",
            "notes",
        ])->all());

        $customer->companies()->attach($company_id, [
            "notes" => $validated["notes"] ?? null,
        ]);

        return response()->json([
            "customer" => $customer,
        ], 201);
    }

    public function show(Request $request, SgcCustomer $sgc_customer)
    {
        abort_unless(
            $sgc_customer->isAccessibleBy($request->user()),
            Response::HTTP_FORBIDDEN
        );

        $sgc_customer->load([
            "addresses",
            "companies" => function ($query) use ($request) {
                $query->accessibleBy($request->user());
            },
        ]);

        return response()->json([
            "customer" => $sgc_customer,
        ], 200);
    }

    public function update(UpdateCustomerRequest $request, SgcCustomer $sgc_customer)
    {
        $validated = $request->validated();
        $company_id = $validated["company_id"];

        $sgc_customer->update(collect($validated)->except([
            "notes",
            "company_id",
        ])->all());

        if( array_key_exists("notes", $validated) ) {
            $sgc_customer->companies()->updateExistingPivot($company_id, [
                "notes" => $validated["notes"],
            ]);
        }

        return response()->json([
            "customer" => $sgc_customer,
        ], 200);
    }

    public function destroy(Request $request, SgcCustomer $sgc_customer)
    {
        abort_unless(
            $sgc_customer->isAccessibleBy($request->user()),
            Response::HTTP_FORBIDDEN
        );

        $sgc_customer->delete();

        return response()->json([
            "message" => "Cliente removido com sucesso.",
        ], 200);
    }

}
