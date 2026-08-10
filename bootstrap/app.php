<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use App\Http\Middleware\EnsureUserHasRole;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\ResolveTenantContext;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__."/../routes/api.php",
        web: __DIR__."/../routes/web.php",
        commands: __DIR__."/../routes/console.php",
        health: "/up",
    )
    ->withMiddleware(function (Middleware $middleware) {
        $str_trusted_proxies = env("TRUSTED_PROXIES", "127.0.0.1,::1,172.18.0.1");

        $trusted_proxies = array_filter(array_map("trim", explode(",", $str_trusted_proxies)));

        $middleware->trustProxies(
            at: $trusted_proxies,
            headers: Request::HEADER_X_FORWARDED_FOR
                | Request::HEADER_X_FORWARDED_HOST
                | Request::HEADER_X_FORWARDED_PORT
                | Request::HEADER_X_FORWARDED_PROTO
                | Request::HEADER_X_FORWARDED_PREFIX
                | Request::HEADER_X_FORWARDED_AWS_ELB,
        );

        $middleware->statefulApi();

        $middleware->redirectGuestsTo(fn() => route("login"));

        $middleware->alias([
            "role" => EnsureUserHasRole::class,
            "tenant" => ResolveTenantContext::class,
        ]);

        $middleware->web(append: [
            HandleInertiaRequests::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is("api/*"),
        );

        $exceptions->render(function (
            \Illuminate\Routing\Exceptions\InvalidSignatureException $e,
            Request $request,
        ) {
            if( $request->is("email/verify/*") ) {
                return redirect()
                    ->route("login")
                    ->with("error", "Link de verificação inválido ou expirado.");
            }
        });
    })->create();
