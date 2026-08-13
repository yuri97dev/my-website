<?php

namespace App\Http\Controllers\Sgc;

use App\Http\Controllers\Controller;
use App\Http\Requests\Sgc\StoreCustomerAddressRequest;
use App\Http\Requests\Sgc\UpdateCustomerAddressRequest;
use App\Models\SgcCustomer;
use App\Models\SgcCustomerAddress;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CustomerAddressController extends Controller
{
    public function index(Request $request, SgcCustomer $sgc_customer)
    {
        abort_unless(
            $sgc_customer->isAccessibleBy($request->user()),
            Response::HTTP_FORBIDDEN
        );

        return response()->json([
            "addresses" => $sgc_customer->addresses()
                ->orderByDesc("id")
                ->get(),
        ], 200);
    }

    public function store(StoreCustomerAddressRequest $request, SgcCustomer $sgc_customer)
    {
        $address = $sgc_customer->addresses()->create($request->validated());

        return response()->json([
            "address" => $address,
        ], 201);
    }

    public function update(
        UpdateCustomerAddressRequest $request,
        SgcCustomer $sgc_customer,
        SgcCustomerAddress $sgc_customer_address,
    ) {
        abort_unless(
            $sgc_customer_address->customer_id === $sgc_customer->id,
            Response::HTTP_NOT_FOUND
        );

        $sgc_customer_address->update($request->validated());

        return response()->json([
            "address" => $sgc_customer_address->fresh(),
        ], 200);
    }

    public function destroy(
        Request $request,
        SgcCustomer $sgc_customer,
        SgcCustomerAddress $sgc_customer_address,
    ) {
        abort_unless(
            $sgc_customer->isAccessibleBy($request->user()),
            Response::HTTP_FORBIDDEN
        );

        abort_unless(
            $sgc_customer_address->customer_id === $sgc_customer->id,
            Response::HTTP_NOT_FOUND
        );

        $sgc_customer_address->delete();

        return response()->json([
            "message" => "Endereço removido com sucesso.",
        ], 200);
    }
}
