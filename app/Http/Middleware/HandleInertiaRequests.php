<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;
use App\Helpers\Meta;
use App\Helpers\ZiggyGroup;
use Tighten\Ziggy\Ziggy;

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
        $theme_preference = $request->cookie("theme");

        $theme_preference = in_array($theme_preference, ["light", "dark", "system"], true)
            ? $theme_preference
            : "light";

        return array_merge(parent::share($request), [
            "appName" => config("app.name"),
            "themePreference" => $theme_preference,
            "theme" => $this->resolveThemeForRendering($request, $theme_preference),
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
                ...(new Ziggy(ZiggyGroup::for($request)))->toArray(),
                "location" => $request->fullUrl(),
            ],
        ]);
    }

    /**
     * Vuetify SSR cannot resolve "system". Always return a concrete theme.
     */
    private function resolveThemeForRendering(Request $request, string $preference): string
    {
        if( $preference === "dark" ) {
            return "dark";
        }

        if( $preference === "system" ) {
            return strtolower((string) $request->header("Sec-CH-Prefers-Color-Scheme", "")) === "dark"
                ? "dark"
                : "light";
        }

        return "light";
    }
}
