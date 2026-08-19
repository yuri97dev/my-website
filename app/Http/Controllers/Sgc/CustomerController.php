<?php

namespace App\Http\Controllers\Sgc;

use App\Helpers\SearchQuery;
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
            "company" => ["nullable", "string", "max:255"],
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

                if( $request->filled("company") ) {
                    $company = $request->input("company");

                    $query->where(function ($query) use ($company) {
                        SearchQuery::applySubstringSearch($query, "sgc_companies.legal_name", $company);

                        $query->orWhere(function ($query) use ($company) {
                            SearchQuery::applySubstringSearch($query, "sgc_companies.trade_name", $company);
                        });
                    });
                }
            })
            ->when($request->filled("id"), function ($query) use ($request) {
                $query->where("sgc_customers.id", $request->integer("id"));
            })
            ->when($request->filled("name"), function ($query) use ($request) {
                SearchQuery::applySubstringSearch(
                    $query,
                    "sgc_customers.name",
                    $request->input("name")
                );
            })
            ->when($request->filled("nickname"), function ($query) use ($request) {
                SearchQuery::applySubstringSearch(
                    $query,
                    "sgc_customers.nickname",
                    $request->input("nickname")
                );
            })
            ->when($request->filled("tax_id"), function ($query) use ($request) {
                SearchQuery::applySubstringSearch(
                    $query,
                    "sgc_customers.tax_id",
                    $request->input("tax_id")
                );
            })
            ->when($request->filled("search"), function ($query) use ($request) {
                SearchQuery::applyWordSearch(
                    $query,
                    "concat_ws(' ', sgc_customers.name, sgc_customers.nickname, sgc_customers.tax_id)",
                    $request->input("search")
                );
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

    public function lookup(Request $request)
    {
        $validated = $request->validate([
            "identity_document" => ["nullable", "string", "max:255"],
            "tax_id" => ["nullable", "string", "max:255"],
        ]);

        $matches = SgcCustomer::findByDocuments(
            $validated["identity_document"] ?? null,
            $validated["tax_id"] ?? null
        );

        if( $matches->isEmpty() ) {
            return response()->json([
                "customer" => null,
            ], 200);
        }

        if( $matches->pluck("id")->unique()->count() > 1 ) {
            $message = "O RG e o CPF/CNPJ pertencem a clientes diferentes.";

            return response()->json([
                "message" => $message,
                "errors" => [
                    "identity_document" => [ $message ],
                    "tax_id" => [ $message ],
                ],
            ], 422);
        }

        $customer = $matches->first();

        $customer->load([
            "addresses",
            "companies" => function ($query) use ($request) {
                $query->accessibleBy($request->user());
            },
        ]);

        return response()->json([
            "customer" => $customer,
        ], 200);
    }

    public function store(StoreCustomerRequest $request)
    {
        $validated = $request->validated();
        $company_id = $validated["company_id"];

        $customer = $this->resolveCustomerForStore($validated);

        $customer->fill(collect($validated)->except([
            "company_id",
            "notes",
            "customer_id",
        ])->all());

        $customer->save();

        $already_attached = $customer->companies()
            ->where("sgc_companies.id", $company_id)
            ->exists();

        if( $already_attached ) {
            if( array_key_exists("notes", $validated) ) {
                $customer->companies()->updateExistingPivot($company_id, [
                    "notes" => $validated["notes"] ?? null,
                ]);
            }
        } else {
            $customer->companies()->attach($company_id, [
                "notes" => $validated["notes"] ?? null,
            ]);
        }

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
        $request->validate([
            "company_id" => ["required", "integer", "exists:sgc_companies,id"],
        ]);

        $company = SgcCompany::query()->findOrFail($request->integer("company_id"));

        abort_unless(
            $company->canBeManagedBy($request->user()),
            Response::HTTP_FORBIDDEN
        );

        abort_unless(
            $sgc_customer->companies()->where("sgc_companies.id", $company->id)->exists(),
            Response::HTTP_NOT_FOUND
        );

        $sgc_customer->companies()->detach($company->id);

        if( $sgc_customer->companies()->count() === 0 ) {
            $sgc_customer->delete();
        }

        return response()->json([
            "message" => "Cliente removido com sucesso.",
        ], 200);
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function resolveCustomerForStore(array $validated): SgcCustomer
    {
        $matches = SgcCustomer::findByDocuments(
            $validated["identity_document"] ?? null,
            $validated["tax_id"] ?? null
        );

        if( $matches->count() === 1 ) {
            return $matches->first();
        }

        if( !empty($validated["customer_id"]) ) {
            return SgcCustomer::query()->findOrFail($validated["customer_id"]);
        }

        return new SgcCustomer();
    }

}
