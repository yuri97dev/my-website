<?php

namespace App\Actions;

use App\Models\PostalCodeAddress;
use Illuminate\Support\Facades\Http;

class ResolvePostalCodeAddress
{
    public function __invoke(string $postalCode): ?PostalCodeAddress
    {
        $normalized = $this->normalizePostalCode($postalCode);

        if( $normalized === null ) {
            return null;
        }

        $address = PostalCodeAddress::query()
            ->where("postal_code", $normalized)
            ->first();

        if( $address ) {
            return $address;
        }

        $attributes = $this->fetchFromBrasilApi($normalized)
            ?? $this->fetchFromViaCep($normalized);

        if( $attributes === null ) {
            return null;
        }

        return PostalCodeAddress::create($attributes);
    }

    public function normalizePostalCode(string $postalCode): ?string
    {
        $digits = preg_replace("/\D/", "", $postalCode);

        if( strlen($digits) !== 8 ) {
            return null;
        }

        return $digits;
    }

    private function fetchFromBrasilApi(string $postalCode): ?array
    {
        $response = Http::timeout(10)
            ->acceptJson()
            ->get("https://brasilapi.com.br/api/cep/v2/{$postalCode}");

        if( !$response->successful() ) {
            return null;
        }

        $data = $response->json();

        if( empty($data["cep"]) ) {
            return null;
        }

        return [
            "postal_code" => preg_replace("/\D/", "", $data["cep"]),
            "address_street" => $data["street"] ?? null,
            "address_district" => $data["neighborhood"] ?? null,
            "address_city" => $data["city"] ?? null,
            "address_state" => $data["state"] ?? null,
            "address_country" => "BR",
        ];
    }

    private function fetchFromViaCep(string $postalCode): ?array
    {
        $response = Http::timeout(10)
            ->acceptJson()
            ->get("https://viacep.com.br/ws/{$postalCode}/json/");

        if( !$response->successful() ) {
            return null;
        }

        $data = $response->json();

        if( !empty($data["erro"]) ) {
            return null;
        }

        return [
            "postal_code" => preg_replace("/\D/", "", $data["cep"] ?? $postalCode),
            "address_street" => $data["logradouro"] ?? null,
            "address_district" => $data["bairro"] ?? null,
            "address_city" => $data["localidade"] ?? null,
            "address_state" => $data["uf"] ?? null,
            "address_country" => "BR",
        ];
    }
}
