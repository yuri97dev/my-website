<?php

namespace App\Http\Controllers;

use App\Actions\ResolvePostalCodeAddress;
use Symfony\Component\HttpFoundation\Response;

class PostalCodeAddressController extends Controller
{
    public function show(string $postal_code, ResolvePostalCodeAddress $resolve_postal_code_address)
    {
        $normalized = $resolve_postal_code_address->normalizePostalCode($postal_code);

        if( $normalized === null ) return response()->json([
            "message" => "CEP inválido.",
        ], Response::HTTP_UNPROCESSABLE_ENTITY);

        $address = $resolve_postal_code_address($postal_code);

        if( $address === null ) return response()->json([
            "message" => "CEP não encontrado.",
        ], Response::HTTP_NOT_FOUND);

        return response()->json([
            "postal_code_address" => $address,
        ], Response::HTTP_OK);
    }
}
