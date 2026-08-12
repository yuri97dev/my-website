<?php

namespace App\Http\Controllers\Sgc;

use App\Http\Controllers\Controller;
use App\Http\Requests\Sgc\StoreCustomerRequest;
use App\Http\Requests\Sgc\UpdateCustomerRequest;
use App\Models\SgcCompany;
use App\Models\SgcCustomer;
use App\Models\User;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            "company_id" => ["required", "integer", "exists:sgc_companies,id"],
        ]);

        $company = SgcCompany::query()->findOrFail($request->integer("company_id"));

        abort_unless(
            $company->isAccessibleBy($request->user()),
            Response::HTTP_FORBIDDEN
        );

        $perPage = min($request->integer("per_page", 15), 100);

        $paginator = $company->customers()
            ->with("addresses")
            ->orderByDesc("sgc_customers.id")
            ->paginate($perPage);

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
            "addresses",
        ])->all());

        $customer->companies()->attach($company_id, [
            "notes" => $validated["notes"] ?? null,
        ]);

        if( !empty($validated["addresses"]) ) {
            $customer->addresses()->createMany($validated["addresses"]);
        }

        $customer->load("addresses");

        return response()->json([
            "customer" => $customer,
        ], 201);
    }

    public function show(Request $request, SgcCustomer $sgcCustomer)
    {
        abort_unless(
            $this->customerAccessibleByUser($sgcCustomer, $request->user()),
            Response::HTTP_FORBIDDEN
        );

        $sgcCustomer->load("addresses");

        return response()->json([
            "customer" => $sgcCustomer,
        ], 200);
    }

    public function update(UpdateCustomerRequest $request, SgcCustomer $sgcCustomer)
    {
        $validated = $request->validated();
        $company_id = $validated["company_id"];

        $sgcCustomer->update(collect($validated)->except([
            "notes",
            "addresses",
            "company_id",
        ])->all());

        if( array_key_exists("notes", $validated) ) {
            $sgcCustomer->companies()->updateExistingPivot($company_id, [
                "notes" => $validated["notes"],
            ]);
        }

        if( array_key_exists("addresses", $validated) ) {
            $this->syncAddresses($sgcCustomer, $validated["addresses"] ?? []);
        }

        $sgcCustomer->load("addresses");

        return response()->json([
            "customer" => $sgcCustomer,
        ], 200);
    }

    public function destroy(Request $request, SgcCustomer $sgcCustomer)
    {
        abort_unless(
            $this->customerAccessibleByUser($sgcCustomer, $request->user()),
            Response::HTTP_FORBIDDEN
        );

        $sgcCustomer->delete();

        return response()->json([
            "message" => "Cliente removido com sucesso.",
        ], 200);
    }

    private function customerAccessibleByUser(SgcCustomer $customer, User $user): bool
    {
        return $customer->companies()
            ->accessibleBy($user)
            ->exists();
    }

    private function syncAddresses(SgcCustomer $customer, array $addresses): void
    {
        $ids = [];

        foreach( $addresses as $address ) {
            if( isset($address["id"]) ) {
                $customer->addresses()
                    ->where("id", $address["id"])
                    ->update(collect($address)->except("id")->all());

                $ids[] = $address["id"];
            } else {
                $created = $customer->addresses()->create($address);
                $ids[] = $created->id;
            }
        }

        $customer->addresses()
            ->whereNotIn("id", $ids)
            ->delete();
    }
}
