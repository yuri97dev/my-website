<?php

namespace App\Http\Controllers;

use App\Helpers\KeyPair;
use App\Helpers\LicenseLimit;
use App\Http\Requests\License\RefreshLicenseTokenRequest;
use App\Http\Requests\License\StoreLicenseRequest;
use App\Http\Requests\License\UpdateLicenseRequest;
use App\Http\Requests\License\UpdateLicenseStatusRequest;
use App\Models\License;
use Carbon\Carbon;
use Exception;
use Firebase\JWT\JWT;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

class LicenseController extends Controller
{
    public function index()
    {
        $max_licenses = LicenseLimit::perUser();

        return Inertia::render(
            "Tools/Licenses",
            compact("max_licenses")
        );
    }

    public function licenses(Request $request)
    {
        $per_page = min($request->integer("per_page", 15), 100);

        $query = License::query()->accessibleBy($request->user());

        $paginator = (clone $query)
            ->orderByDesc("id")
            ->paginate($per_page);

        $used_licenses = (clone $query)
            ->where("status", "!=", License::STATUS_REVOKED)
            ->count();

        return response()->json([
            "current_page"=> $paginator->currentPage(),
            "last_page"=> $paginator->lastPage(),
            "per_page"=> $paginator->perPage(),
            "total"=> $paginator->total(),
            "used_licenses"=> $used_licenses,
            "max_licenses"=> LicenseLimit::perUser(),
            "licenses"=> $paginator->items(),
        ], 200);
    }

    public function store(StoreLicenseRequest $request)
    {
        try {

            $key_pair = KeyPair::generateRSA();

            $license = License::create([
                "user_id"=> $request->user()->id,
                "label"=> $request->input("label"),
                "observations"=> $request->input("observations"),
                "expires_at"=> $request->input("expires_at"),
                "status"=> License::STATUS_PENDING,
                "public_key"=> $key_pair["public"],
                "private_key_encrypted"=> $key_pair["private"],
            ]);

            $token = $this->buildActivationToken($license);

            return response()->json([
                "license"=> $license->fresh(),
                "token"=> $token,
            ], 201);

        } catch(Exception $e) {

            Log::error($e->getMessage());

            return response()->json([
                "message"=> "Ocorreu um erro inesperado",
            ], 400);

        }
    }

    public function update(UpdateLicenseRequest $request, License $license)
    {
        abort_unless(
            $license->isAccessibleBy($request->user()),
            Response::HTTP_FORBIDDEN
        );

        $license->update([
            "label"=> $request->input("label"),
            "observations"=> $request->input("observations"),
            "expires_at"=> $request->input("expires_at"),
        ]);

        return response()->json([
            "license"=> $license->fresh(),
        ], 200);
    }

    public function updateStatus(UpdateLicenseStatusRequest $request, License $license)
    {
        abort_unless(
            $license->isAccessibleBy($request->user()),
            Response::HTTP_FORBIDDEN
        );

        $license->status = $request->input("status");

        if( $license->status === License::STATUS_PENDING ) {
            $license->fingerprint = null;
        }

        $license->save();

        return response()->json([
            "license"=> $license->fresh(),
        ], 200);
    }

    public function destroy(Request $request, License $license)
    {
        abort_unless(
            $license->isAccessibleBy($request->user()),
            Response::HTTP_FORBIDDEN
        );

        $license->delete();

        return response()->json([
            "message"=> "Licença removida com sucesso",
        ], 200);
    }

    public function token(Request $request, License $license)
    {
        abort_unless(
            $license->isAccessibleBy($request->user()),
            Response::HTTP_FORBIDDEN
        );

        if( $license->status !== License::STATUS_PENDING ) {
            return response()->json([
                "message"=> "Token de ativação disponível apenas para licenças pendentes",
            ], 400);
        }

        return response()->json([
            "token"=> $this->buildActivationToken($license),
        ], 200);
    }

    public function refresh(RefreshLicenseTokenRequest $request)
    {
        $license_uuid = $request->input("jwt_payload.sub");

        $license = License::where("uuid", $license_uuid)->firstOrFail();

        if( $license->status === License::STATUS_SUSPENDED ) {
            return response()->json([
                "message"=> "Esta licença está suspensa",
            ], 401);
        }

        if( $license->status === License::STATUS_REVOKED ) {
            return response()->json([
                "message"=> "Esta licença foi revogada",
            ], 401);
        }

        if( $license->status === License::STATUS_ACTIVE ) {
            if( $license->fingerprint !== $request->input("fingerprint") ) {
                return response()->json([
                    "message"=> "Esta licença está associada a outro dispositivo",
                ], 401);
            }
        }

        if( $license->status === License::STATUS_PENDING ) {
            $existing_active = License::where([
                "fingerprint"=> $request->input("fingerprint"),
                "status"=> License::STATUS_ACTIVE,
            ])->exists();

            if( $existing_active ) {
                return response()->json([
                    "message"=> "Dispositivo já possui uma licença ativa",
                ], 401);
            }

            $license->status = License::STATUS_ACTIVE;
            $license->fingerprint = $request->input("fingerprint");

            $license->save();
        }

        $expires_at = Carbon::parse($license->expires_at)->endOfDay();

        if( $expires_at->isPast() ) {
            return response()->json([
                "message"=> "Licença expirada",
            ], 401);
        }

        $expires_at->addDays(15);

        $issued_at = Carbon::now();

        $payload = [
            "iat"=> $issued_at->timestamp,
            "exp"=> $expires_at->timestamp,
            "sub"=> $license->uuid,
            "fingerprint"=> $license->fingerprint,
        ];

        $jwt = JWT::encode($payload, $license->private_key_encrypted, "RS256");

        $license->update([
            "last_validated_at"=> $issued_at,
        ]);

        return response()->json([
            "token"=> $jwt,
            "public_key"=> $license->public_key,
        ], 200);
    }

    private function buildActivationToken(License $license): string
    {
        $issued_at = Carbon::now();
        $expire = $issued_at->copy()->addDay();

        $payload = [
            "iat"=> $issued_at->timestamp,
            "exp"=> $expire->timestamp,
            "sub"=> $license->uuid,
        ];

        return JWT::encode($payload, $license->private_key_encrypted, "RS256");
    }
}
