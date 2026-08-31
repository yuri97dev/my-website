<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;
use Tighten\Ziggy\Ziggy;
use App\Helpers\Meta;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = "app";

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $theme = $request->cookie("theme");

        return array_merge(parent::share($request), [
            "appName" => config("app.name"),
            "theme" => in_array($theme, ["light", "dark", "system"], true)
                ? $theme
                : "system",
            "flash" => fn () => [
                "success" => $request->hasSession()
                    ? $request->session()->get("success")
                    : null,
                "error" => $request->hasSession()
                    ? $request->session()->get("error")
                    : null,
            ],
            "meta" => fn () => Meta::get(),
            "auth.user" => fn () => $request->user()?->only([
                "id",
                "name",
                "email",
                "email_verified_at",
                "accepts_marketing",
            ]) ?? null,
            "auth.isSysAdmin" => fn () => $request->user()?->isSystemAdmin(),
            "auth.sgc" => fn () => $request->user()
                ? ["companies" => $request->user()->sgcCompaniesForAuth()]
                : null,
            "ziggy" => fn () => [
                ...(new Ziggy)->toArray(),
                "location" => $request->fullUrl(),
            ],
        ]);
    }
}
