<?php

namespace App\Http\Middleware;

use App\Models\License;
use Closure;
use Exception;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use UnexpectedValueException;

class DecodeJwtToken
{
    public function handle(Request $request, Closure $next): Response
    {
        try {

            $jwt = $request->bearerToken();

            if( !$jwt ) return response()->json([
                "message"=> "Token Bearer é obrigatório",
            ], 400);

            $license_uuid = $this->getSubjectFromToken($jwt);

            if( empty($license_uuid) ) return response()->json([
                "message"=> "Token inválido",
            ], 400);

            $license = License::where("uuid", $license_uuid)->firstOrFail();
            $jwt_payload = JWT::decode($jwt, new Key($license->public_key, "RS256"));

            $unsigned_token = $license->fingerprint && !isset($jwt_payload->fingerprint);

            if( $unsigned_token ) return response()->json([
                "message"=> "Token não assinado",
            ], 400);

            $request->merge([
                "jwt_payload"=> (array) $jwt_payload,
            ]);

            return $next($request);

        } catch(UnexpectedValueException $e) {

            return response()->json([
                "message"=> "Token inválido",
            ], 400);

        } catch(ExpiredException $e) {

            return response()->json([
                "message"=> "Token expirado",
            ], 400);

        } catch(Exception $e) {

            Log::error($e->getMessage());

            return response()->json([
                "message"=> "Ocorreu um erro inesperado",
            ], 400);

        }
    }

    private function getSubjectFromToken(string $jwt): string
    {
        [ , $payload ] = explode(".", $jwt, 3);
        $data = json_decode(base64_decode(strtr($payload, "-_", "+/")), true);

        return $data["sub"] ?? "";
    }
}
