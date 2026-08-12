<?php

namespace App\Actions;

use App\Models\PostalCodeAddress;
use Illuminate\Support\Facades\Http;

class ResolvePostalCodeAddress
{
    public function __invoke(string $postal_code): ?PostalCodeAddress
    {
        $normalized = $this->normalizePostalCode($postal_code);

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

    public function normalizePostalCode(string $postal_code): ?string
    {
        $digits = preg_replace("/\D/", "", $postal_code);

        if( strlen($digits) !== 8 ) {
            return null;
        }

        return $digits;
    }

    private function fetchFromBrasilApi(string $postal_code): ?array
    {
        $response = Http::timeout(10)
            ->acceptJson()
            ->get("https://brasilapi.com.br/api/cep/v2/{$postal_code}");

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

    private function fetchFromViaCep(string $postal_code): ?array
    {
        $response = Http::timeout(10)
            ->acceptJson()
            ->get("https://viacep.com.br/ws/{$postal_code}/json/");

        if( !$response->successful() ) {
            return null;
        }

        $data = $response->json();

        if( !empty($data["erro"]) ) {
            return null;
        }

        return [
            "postal_code" => preg_replace("/\D/", "", $data["cep"] ?? $postal_code),
            "address_street" => $data["logradouro"] ?? null,
            "address_district" => $data["bairro"] ?? null,
            "address_city" => $data["localidade"] ?? null,
            "address_state" => $data["uf"] ?? null,
            "address_country" => "BR",
        ];
    }
}
